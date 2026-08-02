; ═══════════════════════════════════════════════════════════════
;  牙科诊所管理系统 - Inno Setup 安装脚本
;
;  使用方法：
;  1. 安装 Inno Setup 6 (https://jrsoftware.org/isinfo.php)
;  2. 先运行 build.sh --target win 生成构建产物到 deploy/dist/
;  3. 用 Inno Setup Compiler 打开此文件，点击 Compile
;  5. 生成的 .exe 在 deploy/output/ 目录
; ═══════════════════════════════════════════════════════════════

#define MyAppName "牙科诊所管理系统"
#define MyAppVersion GetFileVersion("..\\VERSION")
#ifndef MyAppVersion
  #define MyAppVersion "1.0.0"
#endif
#define MyAppPublisher "Dental Clinic"
#define MyAppURL "http://localhost/dental"

[Setup]
AppId={{A1B2C3D4-E5F6-7890-ABCD-EF1234567890}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
DefaultDirName=C:\DentalClinic
DefaultGroupName={#MyAppName}
DisableProgramGroupPage=yes
OutputDir=output
OutputBaseFilename=牙科诊所管理系统_安装包_v{#MyAppVersion}
SetupIconFile=
Compression=lzma2/ultra64
SolidCompression=yes
WizardStyle=modern
PrivilegesRequired=admin
DiskSpanning=no
; Win7 版：最低 Windows 7 SP1（NT 6.1 SP1）。
; 运行时组件必须同步保持 Win7 兼容——PHP 8.2.x(VS16)、MySQL 5.7.x、Python 3.8.x。
; 各自的上限原因：PHP 8.3 起要求 Windows 8；MySQL 8.0 支持平台只到 Server 2016；
; Python 3.9 起要求 Windows 8.1。任何一项越界，安装包能启动但装完跑不起来。
MinVersion=6.1sp1
; 仅支持 x64（PHP 8.2 x64 + MySQL 5.7 winx64）
ArchitecturesAllowed=x64
ArchitecturesInstallIn64BitMode=x64
; 中文界面
ShowLanguageDialog=no

[Languages]
Name: "chinesesimplified"; MessagesFile: "compiler:Languages\ChineseSimplified.isl"

[Messages]
chinesesimplified.BeveledLabel=牙科诊所管理系统

[CustomMessages]
chinesesimplified.InstallingDeps=正在配置系统，首次安装需要几分钟，请耐心等待...
chinesesimplified.LaunchAfterInstall=安装完成后启动系统
chinesesimplified.CreateDesktopShortcut=创建桌面快捷方式

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopShortcut}"; GroupDescription: "快捷方式:"; Flags: checked

[Files]
; 项目代码（由 build.sh --target win 构建）
; 排除 laragon / wmf51 / vc_redist —— 它们属于运行环境与前置安装包，
; 各有独立的 DestDir，不该被复制进项目目录（否则白占 1.3GB+）。
Source: "dist\*"; DestDir: "{app}\laragon\www\dental"; Flags: ignoreversion recursesubdirs createallsubdirs; Excludes: "*.bat,*.sh,ocr-wheels,laragon,wmf51,win7-prereq,vc_redist.x64.exe,python-installer.exe"

; ⚠ 这些运行时目录**不得**再加 Check: DirExists(ExpandConstant('{src}\dist\...'))。
;   Check 是在**目标机安装时**求值的，而 {src} 指 setup.exe 所在目录 ——
;   单独分发安装器时它旁边根本没有 dist\，条件恒为假，于是 Laragon/PHP/MySQL、
;   SHA-2 补丁、WMF、.NET、OCR 全部被静默跳过，装完只剩一个空壳。
;   编译期是否存在应交给 ISCC 自己判断：源目录缺失时直接编译失败，
;   这正是我们要的 —— 缺前置的 Win7 包不该被产出。
;   单文件的可选项用 skipifsourcedoesntexist（那是编译期语义，正确）。

; Win7 运行环境：PHP 8.2.33 / MySQL 5.7.44 / Nginx / Composer，由 build.sh 自组装。
; install-win.ps1 从 {app}\laragon\bin\{php,mysql,nginx} 自动发现版本目录。
Source: "dist\laragon\*"; DestDir: "{app}\laragon"; Flags: ignoreversion recursesubdirs createallsubdirs

; VC++ 2015-2022 x64 运行库（PHP VS16 构建的依赖）
Source: "dist\vc_redist.x64.exe"; DestDir: "{app}"; Flags: ignoreversion skipifsourcedoesntexist
; Win7 SHA-2 前置（KB4490628 服务堆栈 + KB4474419 SHA-2 签名支持）。
; 微软自 2019 年起用 SHA-2 重签所有更新，纯净 Win7 SP1 验不了签名，
; 此时 wusa 装 .NET/WMF 会长时间卡在「Searching for updates」而不返回。
; 文件名的 01-/02- 前缀即安装顺序，install-win.bat 按名字排序逐个安装。
Source: "dist\win7-prereq\*"; DestDir: "{app}\win7-prereq"; Flags: ignoreversion recursesubdirs createallsubdirs
; WMF 5.1：Win7 自带 PowerShell 2.0，install-win.bat 会按需静默安装
Source: "dist\wmf51\*"; DestDir: "{app}\wmf51"; Flags: ignoreversion recursesubdirs createallsubdirs
; .NET Framework 4.8：WMF 5.1 要求 .NET 4.5.2+，而纯净 Win7 SP1 只有 3.5.1。
; 不带这个，离线机器装不了 WMF，也就跑不了 install-win.ps1。
Source: "dist\dotnet48\*"; DestDir: "{app}\dotnet48"; Flags: ignoreversion recursesubdirs createallsubdirs

; OCR Python 离线包。这一项是真可选的（build.sh --skip-ocr 就不会产出），
; 所以用 ISPP 的**编译期**条件，而不是运行时 Check —— SourcePath 是本 .iss
; 所在目录，在编译机上求值，语义才对。
#if DirExists(AddBackslash(SourcePath) + "dist\ocr-wheels")
Source: "dist\ocr-wheels\*"; DestDir: "{app}\ocr-wheels"; Flags: ignoreversion recursesubdirs createallsubdirs
#endif
Source: "dist\python-installer.exe"; DestDir: "{app}"; Flags: ignoreversion skipifsourcedoesntexist
Source: "dist\laragon-wamp.exe"; DestDir: "{app}"; Flags: ignoreversion skipifsourcedoesntexist

; 部署脚本（从 dist/ 取，已由 build.sh 转换为 GBK + CRLF，cmd.exe 可正确解析）
; 注意：必须先运行 build.sh --target win 才能编译此安装包
Source: "dist\install-win.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "dist\install-win.ps1"; DestDir: "{app}"; Flags: ignoreversion
Source: "dist\upgrade-win.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "dist\start-win.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "dist\stop-win.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "dist\uninstall-win.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "dist\laragon-startup.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "dist\batch-helpers\*"; DestDir: "{app}\batch-helpers"; Flags: ignoreversion recursesubdirs createallsubdirs

; 环境配置模板
Source: ".env.deploy"; DestDir: "{app}"; Flags: ignoreversion

; VERSION 文件
Source: "..\\VERSION"; DestDir: "{app}"; Flags: ignoreversion

[Icons]
Name: "{group}\{#MyAppName}"; Filename: "{app}\start-win.bat"; IconFilename: "{app}\laragon\laragon.exe"; IconIndex: 0; Comment: "启动牙科诊所管理系统"
Name: "{group}\停止 {#MyAppName}"; Filename: "{app}\stop-win.bat"; Comment: "停止所有服务"
Name: "{group}\卸载 {#MyAppName}"; Filename: "{uninstallexe}"
Name: "{commondesktop}\{#MyAppName}"; Filename: "{app}\start-win.bat"; IconFilename: "{app}\laragon\laragon.exe"; IconIndex: 0; Tasks: desktopicon; Comment: "启动牙科诊所管理系统"

[Run]
; 注意：配置脚本不在这里跑。[Run] 拿不到子进程退出码，install-win.bat 失败
; 或要求重启都会被 runhidden 悄悄吞掉，用户只看到「安装完成」却打不开系统。
; 改由 [Code] 的 CurStepChanged → Exec() 执行，见文件末尾。

; 可选：安装后启动
Filename: "{app}\start-win.bat"; Parameters: """{app}"""; Description: "{cm:LaunchAfterInstall}"; Flags: nowait postinstall skipifsilent shellexec

[UninstallRun]
; 两条都必须带 shellexec —— Inno 的 Filename 只保证能跑 .exe，
; 底层的 CreateProcess 不认 .bat（要跑批处理得先起命令解释器）。
; 少了这个标志，这两条会静默不执行，结果就是
; 「卸载完了，DentalClinicMySQL 服务和三个计划任务还留在系统里」。
; 同一份 .iss 里 start-win.bat 那条本来就带 shellexec，可对照。

; 卸载前停止所有服务
Filename: "{app}\stop-win.bat"; Parameters: """{app}"""; Flags: shellexec runhidden waituntilterminated

; 清理 Inno 不知道的运行时产物：MySQL 数据库、Windows 服务注册、三个计划任务。
; 这些都是 install-win.ps1 在安装后创建的，不在 Inno 的文件清单里，
; 只靠 {uninstallexe} 卸载会把 DentalClinicMySQL 服务和
; DentalClinic-Scheduler/QueueWorker/LogCleanup 计划任务留在系统里。
;
; 必须用 --cleanup-only 而不是 --yes：
;   1. 本条目执行时 unins000.exe 还在 {app} 里跑，--yes 会 rmdir /S 整个 {app}，
;      连 unins000.dat（Inno 的卸载清单）一起删掉，Inno 既清不完文件，
;      也注销不掉「添加/删除程序」条目；正在执行的 .bat 自身被删还会让 cmd 断流。
;   2. --yes 走完流程后有 pause，而这里是 runhidden——没有窗口可以按键，
;      卸载会永久卡死。--cleanup-only 全程无 pause / set /p。
; 不传安装目录：该脚本用 %~dp0 自己定位，多传的位置参数只会被解析循环 shift 掉。
Filename: "{app}\uninstall-win.bat"; Parameters: "--cleanup-only"; Flags: shellexec runhidden waituntilterminated; Check: FileExists(ExpandConstant('{app}\uninstall-win.bat'))

; 兜底：强杀残留进程
Filename: "{cmd}"; Parameters: "/c taskkill /f /im mysqld.exe 2>nul & taskkill /f /im nginx.exe 2>nul & taskkill /f /im python.exe 2>nul"; Flags: runhidden

[UninstallDelete]
; 清理运行时生成的文件
Type: filesandordirs; Name: "{app}\laragon\data"
Type: filesandordirs; Name: "{app}\laragon\www\dental\storage\logs"
Type: filesandordirs; Name: "{app}\laragon\www\dental\bootstrap\cache"
Type: filesandordirs; Name: "{app}\laragon\www\dental\vendor"
Type: filesandordirs; Name: "{app}\ocr-wheels"
Type: filesandordirs; Name: "{app}\backups"
; 安装日志由脚本运行时生成，不在 Inno 的文件清单里，不显式删就会留在磁盘上
Type: filesandordirs; Name: "{app}\logs"

[Code]
// ── 安装前检查 ──────────────────────────────────────────────

function InitializeSetup(): Boolean;
var
  FreeMB: Cardinal;
begin
  Result := True;

  // 检查磁盘空间（至少需要 2GB）
  if GetSpaceOnDisk(ExpandConstant('{sd}'), True, FreeMB, FreeMB) then
  begin
    if FreeMB < 2048 then
    begin
      MsgBox('磁盘空间不足！' + #13#10 + #13#10 +
        '安装至少需要 2GB 可用空间。' + #13#10 +
        '当前可用: ' + IntToStr(FreeMB) + ' MB',
        mbError, MB_OK);
      Result := False;
    end;
  end;
end;

// ── 安装后配置 ──────────────────────────────────────────────
//
// install-win.bat 用 --unattended 调用：窗口不可见，脚本内不得有
// choice / pause / set /p，否则会永久挂起。
// 退出码约定（见 install-win.bat 顶部）：
//   0    配置完成
//   3010 前置组件（.NET 4.8 / WMF 5.1）已装好，需重启后重新运行安装程序
//   其他 失败

const
  RC_REBOOT_REQUIRED = 3010;

procedure CurStepChanged(CurStep: TSetupStep);
var
  ResultCode: Integer;
  AppDir: String;
begin
  if CurStep <> ssPostInstall then
    Exit;

  WizardForm.StatusLabel.Caption := '正在配置系统环境，请稍候...';
  AppDir := ExpandConstant('{app}');

  // 必须经 cmd.exe /C 启动：CreateProcess（Exec 的底层）不认 .bat，
  // 直接把 .bat 当 Filename 传会失败。外层再包一对引号是 cmd /C 的要求——
  // 路径含空格时（如装到 Program Files）少了它就会被拆断。
  // 退出码原样透传：批处理用 exit /b N 结束，cmd /C 返回同一个 N。
  if not Exec(ExpandConstant('{cmd}'),
              '/C ""' + AppDir + '\install-win.bat" --unattended "' + AppDir + '""',
              AppDir,
              SW_HIDE, ewWaitUntilTerminated, ResultCode) then
  begin
    MsgBox('无法启动配置脚本 install-win.bat：' + #13#10 +
           SysErrorMessage(ResultCode) + #13#10 + #13#10 +
           '文件已安装完成，请在安装目录下手动运行 install-win.bat。',
           mbError, MB_OK);
    Exit;
  end;

  if ResultCode = RC_REBOOT_REQUIRED then
  begin
    MsgBox('已为系统安装必需的前置组件（.NET Framework 4.8 / WMF 5.1）。' + #13#10 + #13#10 +
           '请立即重启电脑，重启后在安装目录下运行 install-win.bat' + #13#10 +
           '完成剩余配置，然后才能启动系统。' + #13#10 + #13#10 +
           '安装目录: ' + ExpandConstant('{app}'),
           mbInformation, MB_OK);
    Exit;
  end;

  if ResultCode <> 0 then
    MsgBox('系统配置脚本执行失败（错误码 ' + IntToStr(ResultCode) + '）。' + #13#10 + #13#10 +
           '文件已安装完成，但数据库和服务尚未配置，系统还不能启动。' + #13#10 + #13#10 +
           '完整日志已保存，请据此排查：' + #13#10 +
           '  ' + AppDir + '\logs\install-*.log   配置全过程' + #13#10 +
           '  ' + AppDir + '\logs\prereq.log      前置组件（.NET / WMF）' + #13#10 +
           '  ' + AppDir + '\laragon\data\mysql-error.log   MySQL 启动',
           mbError, MB_OK);
end;

// ── 卸载确认 ─────────────────────────────────────────────────

function InitializeUninstall(): Boolean;
begin
  Result := MsgBox('确定要卸载牙科诊所管理系统吗？' + #13#10 + #13#10 +
    '注意：数据库中的数据将会被删除！' + #13#10 +
    '如需保留数据，请先在系统中执行数据备份。' + #13#10 +
    '（可使用 backup-restore 工具导出数据）',
    mbConfirmation, MB_YESNO) = IDYES;
end;
