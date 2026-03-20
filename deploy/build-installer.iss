; ═══════════════════════════════════════════════════════════════
;  牙科诊所管理系统 - Inno Setup 安装脚本
;
;  使用方法：
;  1. 安装 Inno Setup 6 (https://jrsoftware.org/isinfo.php)
;  2. 先运行 build.sh --target win 生成构建产物到 deploy/dist/
;  3. 准备好 laragon-portable/ 目录（见 build-README.md）
;  4. 用 Inno Setup Compiler 打开此文件，点击 Compile
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
MinVersion=10.0
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
; Laragon Portable（含 PHP、MySQL、Redis、Nginx、Composer）
Source: "laragon-portable\*"; DestDir: "{app}\laragon"; Flags: ignoreversion recursesubdirs createallsubdirs

; 项目代码（由 build.sh --target win 构建）
Source: "dist\*"; DestDir: "{app}\laragon\www\dental"; Flags: ignoreversion recursesubdirs createallsubdirs; Excludes: "*.bat,*.sh,ocr-wheels"

; OCR Python 离线包
Source: "dist\ocr-wheels\*"; DestDir: "{app}\ocr-wheels"; Flags: ignoreversion recursesubdirs createallsubdirs; Check: DirExists(ExpandConstant('{src}\dist\ocr-wheels'))

; 部署脚本
Source: "install-win.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "upgrade-win.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "start-win.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "stop-win.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "laragon-startup.bat"; DestDir: "{app}"; Flags: ignoreversion

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
; 安装完成后自动运行安装配置脚本
Filename: "{app}\install-win.bat"; Parameters: """{app}"""; Description: "{cm:InstallingDeps}"; Flags: runhidden waituntilterminated; StatusMsg: "{cm:InstallingDeps}"

; 可选：安装后启动
Filename: "{app}\start-win.bat"; Parameters: """{app}"""; Description: "{cm:LaunchAfterInstall}"; Flags: nowait postinstall skipifsilent shellexec

[UninstallRun]
; 卸载前停止所有服务
Filename: "{app}\stop-win.bat"; Parameters: """{app}"""; Flags: runhidden waituntilterminated
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

// ── 安装过程中的进度提示 ────────────────────────────────────

procedure CurStepChanged(CurStep: TSetupStep);
begin
  if CurStep = ssPostInstall then
  begin
    WizardForm.StatusLabel.Caption := '正在配置系统环境，请稍候...';
  end;
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
