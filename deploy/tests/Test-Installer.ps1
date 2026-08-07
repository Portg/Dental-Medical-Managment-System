# ═══════════════════════════════════════════════════════════════════════
#  install-win.ps1 的本机验收（不需要 Windows）
#
#  用法:  pwsh -NoProfile -File deploy/tests/Test-Installer.ps1
#
#  为什么存在：这套安装脚本的失败绝大多数是**纯逻辑**问题 ——
#  batch 的 errorlevel 不重置、artisan 依赖当前目录、幂等标记跟不上
#  文件被覆盖、xampp 形态混进 laragon 路径。这些都不需要 Windows 就能验，
#  没有理由每次都让人拷 600MB 装到 Win7 上才发现。
#  改完 install-win.ps1 / build.sh 里的 .bat 模板后先跑这个。
#
#  末尾会列出本机确实覆盖不到的部分，别把「全过」当成「装机一定成功」。
# ═══════════════════════════════════════════════════════════════════════
# install-win.ps1 的本机验收：语法 + 逐函数行为 + 全局静态检查。
# 目的是把「只能在 Win7 目标机上才发现」的问题尽量前移到本机。
# 跑不了的部分（php.exe / httpd.exe / mysqld.exe 是 PE 可执行文件）明确列在末尾。
$ErrorActionPreference = 'Stop'
$fail = 0
function Check($name, $cond, $detail) {
    if ($cond) { Write-Host ("  PASS  " + $name) }
    else { Write-Host ("  FAIL  " + $name + "  -- " + $detail) -ForegroundColor Red; $script:fail++ }
}
function Section($t) { Write-Host ""; Write-Host ("── " + $t + " " + ("─" * [Math]::Max(0, 58 - $t.Length))) }

$repo = Split-Path -Parent $PSScriptRoot
$installer = Join-Path $repo "install-win.ps1"

Section "1. 语法"
$tokens = $null; $errors = $null
$ast = [System.Management.Automation.Language.Parser]::ParseFile($installer, [ref]$tokens, [ref]$errors)
Check "install-win.ps1 解析无误" ($errors.Count -eq 0) (($errors | ForEach-Object { "$($_.Extent.StartLineNumber): $($_.Message)" }) -join '; ')
if ($errors.Count -gt 0) { exit 1 }

Section "2. 变量必须先定义后使用"
# 只查脚本主体里我们自己引入/依赖的关键变量，函数内的局部变量不管。
$text = [System.IO.File]::ReadAllText($installer)
$lines = $text -split "`r?`n"
foreach ($v in @('ARTISAN', 'DB_ENGINE_NAME', 'DB_STATE_DIR', 'DB_CONFIG_FILE', 'DB_RUNTIME_LOG_DIR', 'PROJECT_DIR', 'XAMPP_DIR', 'RUNTIME_FLAVOR')) {
    $def = -1; $use = -1
    for ($i = 0; $i -lt $lines.Count; $i++) {
        if ($def -lt 0 -and $lines[$i] -match ('^\s*\$' + $v + '\s*=')) { $def = $i }
        if ($use -lt 0 -and $lines[$i] -match ('\$' + $v + '\b') -and $lines[$i] -notmatch ('^\s*\$' + $v + '\s*=') -and $lines[$i] -notmatch '^\s*#') { $use = $i }
    }
    Check ("`$$v 先定义(第$($def+1)行)后使用(第$($use+1)行)") ($def -ge 0 -and ($use -lt 0 -or $def -lt $use)) "定义=$($def+1) 使用=$($use+1)"
}

Section "3. artisan 不得依赖当前目录"
$relArtisan = @($lines | Where-Object { $_ -match "@\('artisan'" })
Check "没有裸写的相对 'artisan'" ($relArtisan.Count -eq 0) ("仍有 " + $relArtisan.Count + " 处")
Check "artisan 调用都走 `$ARTISAN" (($text -split '\$ARTISAN,').Count -ge 14) "少于 14 处"

Section "4. Invoke-External 必须捕获输出"
$ie = [regex]::Match($text, '(?s)function Invoke-External \{.*?\n\}').Value
Check "Invoke-External 合并了 stderr (2>&1)" ($ie -match '2>&1') "没有 2>&1"
Check "Invoke-External 失败时输出进异常消息" ($ie -match '\$detail') "throw 里没有带输出"
Check "Invoke-External 调用了 Write-NativeFailure" ($ie -match 'Write-NativeFailure') "没有诊断输出"

Section "5. Invoke-External 的退出码不得漏进输出流"
$leak = @()
for ($i = 0; $i -lt $lines.Count; $i++) {
    $l = $lines[$i]
    if ($l -match '^\s+Invoke-External ' -and $l -notmatch '\| Out-Null' -and ($l -split 'Invoke-External')[0] -notmatch '=') {
        $leak += ($i + 1)
    }
}
Check "没有未接收返回值的 Invoke-External 语句" ($leak.Count -eq 0) ("第 " + ($leak -join ', ') + " 行")

Section "6. 数据库路径/名称按形态分流（真执行那段分支）"
# 静态 grep 分不清「在 laragon 分支里用 laragon 路径」和「写死」，
# 所以把那段 if/else 抽出来按两种形态各执行一遍，看产出的值。
# 必须连 else 分支一起截，否则 laragon 那轮什么都没赋值、检查会假过。
$flavorBlock = [regex]::Match($text,
    '(?s)if \(\$RUNTIME_FLAVOR -eq "xampp"\) \{\s*\r?\n\s*\$DB_ENGINE_NAME.*?\n\} else \{.*?\n\}').Value
Check "取到形态分流代码块（含 else）" ($flavorBlock -match '(?s)else \{.*\$DB_ENGINE_NAME') "正则没截到 else 分支"
if ($flavorBlock) {
    foreach ($flavor in @('xampp', 'laragon')) {
        $RUNTIME_FLAVOR = $flavor
        # 不用 C:\ ：macOS 上的 Join-Path 会校验盘符存在。这条检查只关心
        # 产出的路径里有没有出现「另一种运行时」的目录名。
        $XAMPP_DIR   = '/DentalClinic/xampp'
        $LARAGON_DIR = '/DentalClinic/laragon'
        $DB_ENGINE_NAME = $null; $DB_STATE_DIR = $null; $DB_CONFIG_FILE = $null; $DB_RUNTIME_LOG_DIR = $null
        Invoke-Expression $flavorBlock
        $paths = @($DB_STATE_DIR, $DB_CONFIG_FILE, $DB_RUNTIME_LOG_DIR)
        $wrongRuntime = if ($flavor -eq 'xampp') { 'laragon' } else { 'xampp' }
        $hits = @($paths | Where-Object { $_ -like ("*" + $wrongRuntime + "*") })
        Check ("$flavor 形态: 数据库路径不含 $wrongRuntime") ($hits.Count -eq 0) ($hits -join ', ')
        $expectEngine = if ($flavor -eq 'xampp') { 'MariaDB' } else { 'MySQL' }
        Check ("$flavor 形态: 引擎名为 $expectEngine") ($DB_ENGINE_NAME -eq $expectEngine) "实际: $DB_ENGINE_NAME"
        Write-Host ("          my.ini -> " + $DB_CONFIG_FILE)
        Write-Host ("          日志   -> " + $DB_RUNTIME_LOG_DIR)
    }
    # 横幅对齐：MariaDB(7) 和 MySQL(5) 都要填到 13 列，负数会让 " " * n 抛异常
    foreach ($n in @('MariaDB', 'MySQL')) {
        Check ("横幅填充列数为正 ($n)") ((13 - $n.Length) -gt 0) ("13 - " + $n.Length)
    }
}

Section "7. 路径重写函数（真执行）"
$wanted = @('Fail-Step', 'Repair-XamppHardcodedPaths')
$funcs = $ast.FindAll({ $args[0] -is [System.Management.Automation.Language.FunctionDefinitionAst] }, $true) |
         Where-Object { $wanted -contains $_.Name }
foreach ($f in $funcs) { Invoke-Expression $f.Extent.Text }

$sandbox = Join-Path ([System.IO.Path]::GetTempPath()) ("inst-" + [guid]::NewGuid().ToString('N').Substring(0, 8))
$root = $sandbox + '/C:\DentalClinic\xampp'
$src = Join-Path $repo 'dist/xampp'
# dist/ 可能是升级包的产物（--upgrade 不带运行时），此时这一节没有素材可测。
# 必须显式说「跳过」而不是崩掉，也不能静默当成通过。
$haveRuntime = (Test-Path (Join-Path $src 'php/php.ini'))
if (-not $haveRuntime) {
    Write-Host "  跳过  dist/ 里没有 xampp 运行时（当前是升级包构建产物）"
    Write-Host "        需要全量构建产物才能测路径重写与扩展启用："
    Write-Host "        ./deploy/build.sh --target win --runtime xampp --keep-dist"
}
if ($haveRuntime) {
foreach ($rel in @('php', 'php/extras', 'mysql/bin', 'apache/conf/extra')) {
    New-Item -ItemType Directory -Path (Join-Path $root $rel) -Force | Out-Null
}
Copy-Item (Join-Path $src 'php/extras/browscap.ini') (Join-Path $root 'php/extras/browscap.ini') -Force
foreach ($rel in @('php/php.ini', 'mysql/bin/my.ini', 'apache/conf/httpd.conf')) {
    Copy-Item (Join-Path $src $rel) (Join-Path $root ($rel -replace '/', '\')) -Force
}
foreach ($c in Get-ChildItem (Join-Path $src 'apache/conf/extra') -Filter '*.conf') {
    Copy-Item $c.FullName (Join-Path $root ('apache\conf\extra\' + $c.Name)) -Force
}

$anchor = "(?<=[`"'=\s])"
function Get-Work($t) {
    $n = 0
    foreach ($p in @(($anchor + '\\{2}xampp(?=\\{2})'), ($anchor + '\\xampp(?=\\)'), ($anchor + '/xampp(?=/)'))) {
        $n += ([regex]::Matches($t, $p)).Count
    }
    return $n + ([regex]::Matches($t, [regex]::Escape('shmcb:/xampp/'))).Count
}
function All-Configs { @(Get-ChildItem -Path $root -Recurse -File | Where-Object { $_.Name -like '*.ini' -or $_.Name -like '*.conf' }) }
function Total-Work { $n = 0; foreach ($f in All-Configs) { $n += Get-Work ([System.IO.File]::ReadAllText($f.FullName)) }; return $n }

$w0 = Total-Work
$r1 = Repair-XamppHardcodedPaths -XamppDir $root
Check "首次改写 ($($r1.Changed)/$($r1.Total) 个文件, $w0 -> $(Total-Work) 处)" ((Total-Work) -eq 0) "仍有残留"
$snap = @{}; foreach ($f in All-Configs) { $snap[$f.FullName] = [System.IO.File]::ReadAllText($f.FullName) }
$r2 = Repair-XamppHardcodedPaths -XamppDir $root
$drift = @($snap.Keys | Where-Object { $snap[$_] -ne [System.IO.File]::ReadAllText($_) })
Check "重复执行零改动且逐字节不变" ($r2.Changed -eq 0 -and $drift.Count -eq 0) "Changed=$($r2.Changed) drift=$($drift.Count)"
Check "不留标记文件" (-not (Test-Path (Join-Path $root '.dental-xampp-root'))) "写了标记"
# 装过旧版的机器上会残留标记文件，新版应当顺手清掉
[System.IO.File]::WriteAllText((Join-Path $root '.dental-xampp-root'), $root)
Repair-XamppHardcodedPaths -XamppDir $root | Out-Null
Check "清掉旧版残留的标记文件" (-not (Test-Path (Join-Path $root '.dental-xampp-root'))) "旧标记还在"
# 重装：setup.bat 的 xcopy /Y 把原始配置盖回来
Copy-Item (Join-Path $src 'php/php.ini') (Join-Path $root 'php\php.ini') -Force
$r3 = Repair-XamppHardcodedPaths -XamppDir $root
Check "重装后自愈 (Changed=$($r3.Changed), 残留 $(Total-Work) 处)" ($r3.Changed -eq 1 -and (Total-Work) -eq 0) "未自愈"
$bc = ([System.IO.File]::ReadAllText((Join-Path $root 'php\php.ini')) -split "`r?`n" | Where-Object { $_ -match '^\s*browscap\s*=' })
Check "browscap 指向的文件真实存在" (Test-Path ($root + '\php\extras\browscap.ini')) "不存在"
Check "browscap 是绝对路径" ($bc -like '*C:\DentalClinic\xampp\php\extras\browscap.ini"') "实际: $bc"

}

Section "7b. PHP 扩展启用（真的改一份 php.ini 副本）"
# 2026-08-06 22:39 那次装机死在 [9/19] key:generate，真因是 XAMPP 的 php.ini
# 默认注释掉了 zip，而 spatie/laravel-backup 的 config 在加载期就用
# ZipArchive::CM_DEFAULT。这一组把「该开的开了、没 DLL 的不写、可重复执行」钉住。
$extFuncs = $ast.FindAll({ $args[0] -is [System.Management.Automation.Language.FunctionDefinitionAst] }, $true) |
            Where-Object { $_.Name -eq 'Ensure-PhpExtensions' }
Check "取到 Ensure-PhpExtensions" ($extFuncs.Count -eq 1) "没找到"
foreach ($f in $extFuncs) { Invoke-Expression $f.Extent.Text }

# 从脚本里读出两份清单和原因表，避免测试和实现各写一遍
$toEnable = @()
if ($text -match '\$script:PhpExtensionsToEnable\s*=\s*@\(([^)]*)\)') {
    $toEnable = @($Matches[1] -split ',' | ForEach-Object { $_.Trim().Trim("'") } | Where-Object { $_ })
}
$required = @()
if ($text -match '\$script:PhpExtensionsRequired\s*=\s*@\(([^)]*)\)') {
    $required = @($Matches[1] -split ',' | ForEach-Object { $_.Trim().Trim("'") } | Where-Object { $_ })
}
Check "解析到待启用清单 ($($toEnable -join ', '))" ($toEnable.Count -gt 0) "没解析到"
Check "断言清单覆盖待启用清单" (@($toEnable | Where-Object { $required -notcontains $_ }).Count -eq 0) `
      ("缺: " + (@($toEnable | Where-Object { $required -notcontains $_ }) -join ', '))
$whyKeys = @()
foreach ($m in [regex]::Matches($text, "(?m)^\s*'([a-z_]+)'\s*=\s*'[^']+'")) { $whyKeys += $m.Groups[1].Value }
Check "每个必需扩展都有中文原因说明" (@($required | Where-Object { $whyKeys -notcontains $_ }).Count -eq 0) `
      ("缺说明: " + (@($required | Where-Object { $whyKeys -notcontains $_ }) -join ', '))

$phpSrc = Join-Path $repo 'dist/xampp/php'
if (Test-Path (Join-Path $phpSrc 'php.ini')) {
    # 每个待启用扩展在包里必须真有 DLL，否则写了 extension= 只会让 php.exe
    # 每次启动都报 Unable to load dynamic library
    foreach ($e in $toEnable) {
        Check ("包内有 ext\php_$e.dll") (Test-Path (Join-Path $phpSrc ("ext/php_" + $e + ".dll"))) "缺 DLL"
    }
    # 内置扩展不该被列入待启用（它们没有 DLL，也没有 ini 行）
    foreach ($e in @('bcmath', 'ctype', 'iconv', 'json', 'dom')) {
        Check ("内置扩展 $e 不在待启用清单里") ($toEnable -notcontains $e) "误列为需 enable"
    }

    $iniSandbox = Join-Path ([System.IO.Path]::GetTempPath()) ("phpini-" + [guid]::NewGuid().ToString('N').Substring(0, 8))
    New-Item -ItemType Directory -Path (Join-Path $iniSandbox 'ext') -Force | Out-Null
    Copy-Item (Join-Path $phpSrc 'php.ini') (Join-Path $iniSandbox 'php.ini') -Force
    foreach ($e in $toEnable) { Set-Content -Path (Join-Path $iniSandbox ("ext/php_" + $e + ".dll")) -Value 'stub' }
    # 故意给一个 ext\ 下没有的扩展，验证它被报成 MissingDll 而不是写进 ini
    $r1 = Ensure-PhpExtensions -PhpDir $iniSandbox -Required (@($toEnable) + @('nosuchext'))
    $iniAfter = [System.IO.File]::ReadAllText((Join-Path $iniSandbox 'php.ini'))
    foreach ($e in $toEnable) {
        Check ("php.ini 里 $e 已启用（行首无分号）") ($iniAfter -match ("(?m)^extension=" + $e + "\s*$")) "没启用"
        Check ("php.ini 里不再有被注释的 $e") (-not ($iniAfter -match ("(?m)^\s*;\s*extension\s*=\s*" + $e + "\s*$"))) "注释行还在"
    }
    Check "缺 DLL 的扩展被报告而不是写进 ini" `
          ($r1.MissingDll -contains 'nosuchext' -and -not ($iniAfter -match 'nosuchext')) `
          ("MissingDll=" + ($r1.MissingDll -join ','))
    Check "首次调用报告了启用项 ($($r1.Enabled -join ', '))" ($r1.Enabled.Count -eq $toEnable.Count) "数量不符"
    # 幂等：再跑一次应当零改动
    $before = $iniAfter
    $r2 = Ensure-PhpExtensions -PhpDir $iniSandbox -Required $toEnable
    $after = [System.IO.File]::ReadAllText((Join-Path $iniSandbox 'php.ini'))
    Check "重复执行零启用且 php.ini 逐字节不变" ($r2.Enabled.Count -eq 0 -and $after -eq $before) `
          ("Enabled=" + $r2.Enabled.Count + " 内容变化=" + ($after -ne $before))
    Remove-Item $iniSandbox -Recurse -Force -ErrorAction SilentlyContinue
}

Section "8. .bat 模板：errorlevel 用法"
$batFiles = @('build.sh', 'install-win.bat', 'start-win.bat', 'stop-win.bat', 'uninstall-win.bat')
$staleErr = @()
foreach ($bf in $batFiles) {
    $p = Join-Path $repo $bf
    if (-not (Test-Path $p)) { continue }
    $bl = @([System.IO.File]::ReadAllLines($p))
    for ($i = 0; $i -lt $bl.Count - 1; $i++) {
        if ($bl[$i] -match '(?i)^\s*if\s+(not\s+)?exist\s+.*\s(mkdir|copy|xcopy|call|del|move)\b') {
            for ($j = $i + 1; $j -lt [Math]::Min($i + 4, $bl.Count); $j++) {
                $s = $bl[$j].Trim()
                if (-not $s -or $s -match '(?i)^rem') { continue }
                if ($s -match '(?i)^if\s+errorlevel\s+\d') { $staleErr += ("{0}:{1}" -f $bf, ($i + 1)) }
                break
            }
        }
    }
}
Check "没有『条件执行后判 errorlevel』(errorlevel 不会被重置)" ($staleErr.Count -eq 0) ($staleErr -join ', ')

Section "8b. .bat 括号块内的两类陷阱"
# ( ) 块在 DisableDelayedExpansion 下整块一次性展开，块内读 %ERRORLEVEL%
# 拿到的是进块之前的值；块内放 :label 在 cmd 里行为也是坏的。
# 注意 `if errorlevel N` 是特殊语法、执行时读实时值，块内使用是**正确**的，不能误报。
function Get-BatSources {
    $out = @{}
    $bs = [System.IO.File]::ReadAllLines((Join-Path $repo 'build.sh'))
    $cur = $null; $buf = New-Object System.Collections.Generic.List[string]
    foreach ($l in $bs) {
        if ($cur) {
            if ($l -match ("^" + $cur + "\s*$")) { $out["build.sh:" + $cur] = $buf.ToArray(); $cur = $null; $buf.Clear(); continue }
            $buf.Add($l)
        } elseif ($l -match "<<'([A-Z_]*BAT)'") { $cur = $Matches[1] }
    }
    foreach ($f in @('install-win.bat', 'start-win.bat', 'stop-win.bat', 'uninstall-win.bat')) {
        $p = Join-Path $repo $f
        if (Test-Path $p) { $out[$f] = [System.IO.File]::ReadAllLines($p) }
    }
    return $out
}
# 判据要两条同时成立：括号深度 > 0 **且本行有缩进**。
# 光看深度会漂（顶层的 :log / set "SETUP_RC=%ERRORLEVEL%" 会被误报）；
# 这些 .bat 里块体一律缩进、顶层子程序标签一律在第 0 列，加上缩进条件就准了。
$errlvlInBlock = @(); $labelInBlock = @()
foreach ($kv in (Get-BatSources).GetEnumerator()) {
    $depth = 0
    for ($i = 0; $i -lt $kv.Value.Count; $i++) {
        $raw = $kv.Value[$i]
        $s = $raw.Trim()
        if ($s -match '^(rem\b|::)') { continue }
        if ($depth -gt 0 -and $raw -match '^\s+') {
            if ($s -match '%ERRORLEVEL%') { $errlvlInBlock += ("{0}:{1}" -f $kv.Key, ($i + 1)) }
            if ($s -match '^:[A-Za-z_]') { $labelInBlock += ("{0}:{1}" -f $kv.Key, ($i + 1)) }
        }
        if ($s -match '\($') { $depth++ }
        if ($s -match '^\)') { $depth--; if ($s -match '\($') { $depth++ } }
        if ($depth -lt 0) { $depth = 0 }
    }
}
# 本次改动的两个模板必须干净；stop-win/start-win/uninstall-win 里的既有问题
# 只报出来不当失败（属另一件事，不在这次范围内，避免顺手大改）。
# 这两条以前对既有 .bat 只是「注意」不算失败。2026-08-06 之后升级为硬失败：
# 那 5 处块内标签全在 stop-win.bat / start-win.bat 里，而这两个脚本因为横幅
# 的裸管道从来没跑通过 —— 隐患一直被掩着。裸管道修好后它们开始真正执行，
# 块内标签就会立刻变成实际故障，所以不能再放过。
Check "括号块内没有读 %ERRORLEVEL%" ($errlvlInBlock.Count -eq 0) ($errlvlInBlock -join ', ')
Check "括号块内没有 :label" ($labelInBlock.Count -eq 0) ($labelInBlock -join ', ')

Section "8c. setup.bat 必须留下自己的日志"
$setupTpl = (Get-BatSources)['build.sh:SHORTCUT_BAT']
Check "取到 setup.bat 模板" ($setupTpl -and $setupTpl.Count -gt 0) "没取到"
if ($setupTpl) {
    $tplText = $setupTpl -join "`n"
    Check "setup.bat 写 logs\setup.log" ($tplText -match 'SETUP_LOG=.*logs\\setup\.log') "没有日志文件"
    Check "xcopy 的 stderr 进日志（不再 2>&1 到 nul）" ($tplText -match 'xcopy .*2>>"%SETUP_LOG%"') "xcopy 还在丢错误"
    Check "copy 的 stderr 进日志" ($tplText -match 'copy "%~1" "%~2" /Y >nul 2>>"%SETUP_LOG%"') "copy 还在丢错误"
    Check "复制失败时提示日志位置" ($tplText -match '(?s):copy_failed.*%SETUP_LOG%') "没提示"
    Check "定义了 :log / :log_rc" ($tplText -match '(?m)^:log\s*$' -and $tplText -match '(?m)^:log_rc\s*$') "缺少日志子程序"
}

Section "8d. Apache 托管：xampp 形态的启停对称性"
# 之前 $APACHE_EXE 全文只用于 httpd -t，Apache 从未被启动；start-win.bat 的
# WEB_MODE 只有 laragon/nginx/php-builtin，xampp 装完会退到 php -S（内置开发
# 服务器）。这一组检查把「注册→启动→停止→卸载」四个环节钉住，防止再退化。
$SVC = 'DentalClinicApache'
$psText = $text   # install-win.ps1
$startBat = [System.IO.File]::ReadAllText((Join-Path $repo 'start-win.bat'))
$stopBat  = [System.IO.File]::ReadAllText((Join-Path $repo 'stop-win.bat'))
$uninBat  = [System.IO.File]::ReadAllText((Join-Path $repo 'uninstall-win.bat'))
$setupTplText = ((Get-BatSources)['build.sh:SHORTCUT_BAT'] -join "`n")

Check "install-win.ps1 注册 $SVC 服务" ($psText -match "-k'?,?\s*'?install" -and $psText -match [regex]::Escape($SVC)) "没有 httpd -k install"
Check "install-win.ps1 重装前先卸旧服务（binPath 需刷新）" ($psText -match "'-k',\s*'uninstall'") "没有 -k uninstall"
Check "install-win.ps1 启动后验证 80 端口" ($psText -match 'Test-HttpEndpoint -Url .http://127\.0\.0\.1/.') "没有端口验证"
Check "start-win.bat 有 apache 分支" ($startBat -match 'WEB_MODE=apache') "没有 WEB_MODE=apache"
Check "start-win.bat 启动 $SVC" ($startBat -match ('net start %APACHE_SERVICE%|net start ' + $SVC)) "没有 net start"
Check "stop-win.bat 停止 $SVC" ($stopBat -match ('net stop %APACHE_SERVICE%|net stop ' + $SVC)) "没有 net stop"
Check "uninstall-win.bat 卸载 $SVC" ($uninBat -match [regex]::Escape($SVC)) "没有处理该服务"
Check "setup.bat 复制前停止 $SVC（否则 xcopy 撞锁）" ($setupTplText -match ('net stop ' + $SVC)) "没有先停 Apache"

# xampp 形态下三个脚本的 PROJECT_DIR 必须一致
foreach ($f in @(@{n='start-win.bat';t=$startBat}, @{n='stop-win.bat';t=$stopBat})) {
    Check ("$($f.n) 的 PROJECT_DIR 按形态分流到 xampp\htdocs\dental") `
          ($f.t -match 'RUNTIME_FLAVOR%"=="xampp" set "PROJECT_DIR=%XAMPP_DIR%\\htdocs\\dental') "没有分流"
}
# start-win.bat 的 my.ini 路径必须和 install-win.ps1 写的位置一致
Check "start-win.bat 的 MYSQL_INI 指向 xampp\mysql\my.ini（与 `$DB_CONFIG_FILE 一致）" `
      ($startBat -match 'MYSQL_INI=%XAMPP_DIR%\\mysql\\my\.ini' -and $psText -match 'DB_CONFIG_FILE\s*=\s*Join-Path \$XAMPP_DIR "mysql\\my\.ini"') "两处不一致"
# xampp 用 mod_php，不该再去起 php-cgi
Check "start-win.bat 在 xampp 下清空 PHP_CGI_EXE（mod_php 不需要）" ($startBat -match 'PHP_CGI_EXE=""|set "PHP_CGI_EXE="') "仍会尝试 php-cgi"

Section "8e. 升级包路径（upgrade-win.bat）"
$upgBat = [System.IO.File]::ReadAllText((Join-Path $repo 'upgrade-win.bat'))
Check "upgrade-win.bat 有运行时形态判定" ($upgBat -match 'RUNTIME_FLAVOR=xampp') "没有，xampp 升级会报『未找到 artisan』"
Check "upgrade-win.bat 的 PROJECT_DIR 按形态分流" `
      ($upgBat -match 'RUNTIME_FLAVOR%"=="xampp" set "PROJECT_DIR=%XAMPP_DIR%\\htdocs\\dental') "没有分流"
Check "upgrade-win.bat 在 xampp 下用扁平的 PHP/MySQL 路径" `
      ($upgBat -match 'PHP_DIR=%XAMPP_DIR%\\php' -and $upgBat -match 'MYSQL_DIR=%XAMPP_DIR%\\mysql') "仍只按 laragon 布局探测"
Check "部署脚本不被复制进项目目录" ($upgBat -match 'echo install-win\.ps1>>') "会污染 htdocs\dental"
Check "升级会刷新 %INSTALL_DIR% 下的部署脚本" ($upgBat -match '刷新部署脚本') "脚本修复送不到目标机"
# 三个脚本对形态的判据必须完全一致，否则会出现「装的是 xampp、升级当成 laragon」
$flavorProbe = 'if exist "%XAMPP_DIR%\apache\bin\httpd.exe" set "RUNTIME_FLAVOR=xampp"'
foreach ($f in @('start-win.bat', 'stop-win.bat', 'upgrade-win.bat')) {
    $c = [System.IO.File]::ReadAllText((Join-Path $repo $f))
    Check ("$f 用同一条形态判据") ($c.Contains($flavorProbe)) "判据不一致"
}

Section "8f. echo 行不得有裸管道（会中止整个批处理）"
# 2026-08-06 23:19 的 setup.log：调 stop-win.bat 后只打出第一行横幅，接着
# 「The syntax of the command is incorrect.」然后什么都没有了。原因是
#   echo  |   牙科诊所管理系统 - 停止服务   |
# 里的 | 是未转义的管道，行尾那个使右侧为空 → 语法错误 → cmd 中止批处理。
# start/stop/uninstall/upgrade-win.bat 因此从来没跑通过。
$INTENT_CMDS = @('findstr', 'find', 'more', 'sort', 'clip', 'nul', 'tasklist', 'wmic', 'ping')
function Count-NakedPipes($lines) {
    $naked = 0
    foreach ($l in $lines) {
        if (-not $l.Trim().ToLower().StartsWith('echo')) { continue }
        for ($j = 0; $j -lt $l.Length; $j++) {
            if ($l[$j] -ne '|') { continue }
            if ($j -gt 0 -and $l[$j - 1] -eq '^') { continue }   # 已转义
            $rest = $l.Substring($j + 1).TrimStart()
            $word = ($rest -split ' ')[0].ToLower().TrimStart('/')
            $isIntent = $false
            foreach ($c in $INTENT_CMDS) { if ($word.StartsWith($c)) { $isIntent = $true; break } }
            if (-not $isIntent) { $naked++ }
        }
    }
    return $naked
}
foreach ($f in @('start-win.bat', 'stop-win.bat', 'uninstall-win.bat', 'upgrade-win.bat', 'install-win.bat')) {
    $p = Join-Path $repo $f
    if (-not (Test-Path $p)) { continue }
    $n = Count-NakedPipes ([System.IO.File]::ReadAllLines($p))
    Check ("$f 的 echo 行没有裸管道") ($n -eq 0) "$n 处"
}
foreach ($kv in (Get-BatSources).GetEnumerator()) {
    if ($kv.Key -notlike 'build.sh:*') { continue }
    $n = Count-NakedPipes $kv.Value
    Check ("$($kv.Key) 的 echo 行没有裸管道") ($n -eq 0) "$n 处"
}

Section "8g. 需要 cmd 解析的命令必须走 Invoke-CmdLine"
# PowerShell 的原生参数数组会吞掉参数值里的内层引号。
# sc.exe 的 binPath= 和 schtasks 的 /tr 都是「一个参数里再带引号」的形式，
# 直接经参数数组传会分别得到 1639 和「Invalid argument/option - '/c'」。
Check "sc.exe create 走 Invoke-CmdLine" ($text -match 'Invoke-CmdLine -CommandLine \$scCmdLine') "仍走参数数组"
Check "binPath 的内层引号被转义" ($text -match "binPathInner -replace") "没有转义"
Check "DisplayName 的值加了引号" ($text -match 'DisplayName= "DentalClinic MySQL"') "带空格的值未加引号"
Check "LogCleanup 任务指向独立 .bat" ($text -match 'clean-logs\.bat') "仍在 /tr 里套多层引号"
Check "LogCleanup 的 schtasks 走 Invoke-CmdLine" ($text -match 'Invoke-CmdLine -CommandLine \(.schtasks\.exe /create /tn "DentalClinic-LogCleanup"') "仍走参数数组"
Check "route:list 不再传 --compact（Laravel 11 无此选项）" `
      (-not ($text -match "'route:list', '--compact'")) "仍在传 --compact"

Section "8h. OCR requirements 必须能被 pip 在 GBK 区域下解码"
# pip 的 auto_decode 只认前两行的 coding 声明或 BOM，否则按系统区域编码解码。
# 中文 Windows 上那是 GBK，UTF-8 的中文注释会让它抛 UnicodeDecodeError，
# 离线安装整个失败（2026-08-06 那次 OCR 就是这样降级的）。
$gbk = [System.Text.Encoding]::GetEncoding(936)
foreach ($rel in @('scripts/requirements-lock.txt', 'scripts/requirements.txt')) {
    $p = Join-Path (Split-Path -Parent $repo) $rel
    if (-not (Test-Path $p)) { continue }
    $bytes = [System.IO.File]::ReadAllBytes($p)
    $head = [System.Text.Encoding]::ASCII.GetString($bytes, 0, [Math]::Min(200, $bytes.Length))
    $hasDecl = ($head -split "`n")[0..1] -match 'coding[:=]'
    $hasBom = ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF)
    $pureAscii = -not (@($bytes | Where-Object { $_ -gt 127 }).Count -gt 0)
    Check ("$rel 有 coding 声明/BOM 或纯 ASCII") ($hasDecl -or $hasBom -or $pureAscii) `
          "非 ASCII 且无声明 —— 目标机上 pip 会 UnicodeDecodeError"
}

Section "9. 打进包的 .env 模板不含真凭据"
$envDeploy = Join-Path $repo 'dist/.env.deploy'
if (Test-Path $envDeploy) {
    $bad = @()
    foreach ($l in [System.IO.File]::ReadAllLines($envDeploy)) {
        if ($l -match '^\s*#' -or $l -notmatch '=') { continue }
        $k, $v = ($l -split '=', 2)
        if ($k -match '(?i)(password|secret|token|_key)$' -and $v -and
            $v -notmatch '^\{\{.*\}\}$' -and $v -notmatch '^(null|)$' -and $v -notmatch '^"\$\{') { $bad += $k }
    }
    Check "敏感键都是占位符/空值" ($bad.Count -eq 0) ("可疑: " + ($bad -join ', '))
}

Section "10. 包里不含构建机的业务数据"
$distStorage = Join-Path $repo 'dist/storage/app'
if (Test-Path $distStorage) {
    $files = @(Get-ChildItem $distStorage -Recurse -File | Where-Object { $_.Name -ne '.gitkeep' })
    Check "dist/storage/app 只有 .gitkeep" ($files.Count -eq 0) ("多出 " + $files.Count + " 个文件: " + (($files | ForEach-Object { $_.Name }) -join ', '))
}

Remove-Item $sandbox -Recurse -Force -ErrorAction SilentlyContinue
Write-Host ""
Write-Host "════════════════════════════════════════════════════════════"
Write-Host ("失败项: " + $fail)
Write-Host ""
Write-Host "本机无法覆盖（需要 Windows 才能执行的部分）:"
Write-Host "  - php.exe / httpd.exe / mysqld.exe 是 PE 可执行文件，本机跑不了"
Write-Host "  - Apache 用重写后的 httpd.conf 实际启动"
Write-Host "  - PowerShell 2.0 专属行为（Set-Location 与进程 cwd 是否同步、"
Write-Host "    Start-Transcript 不记原生输出）—— 只能靠不依赖该行为的写法规避"
Write-Host "  - Windows 服务注册 (sc.exe / nssm) 与计划任务"
exit $(if ($fail) { 1 } else { 0 })
