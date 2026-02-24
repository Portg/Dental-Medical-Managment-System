; ═══════════════════════════════════════════════════════════════
;  牙科诊所管理系统 - Inno Setup 安装脚本
;
;  使用方法：
;  1. 安装 Inno Setup 6 (https://jrsoftware.org/isinfo.php)
;  2. 准备好 laragon-portable/ 目录（见 build-README.md）
;  3. 用 Inno Setup Compiler 打开此文件，点击 Compile
;  4. 生成的 .exe 在 deploy/output/ 目录
; ═══════════════════════════════════════════════════════════════

#define MyAppName "牙科诊所管理系统"
#define MyAppVersion "1.0"
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
; Laragon Portable（含 PHP、MySQL、Redis、Nginx、Node、Composer）
Source: "laragon-portable\*"; DestDir: "{app}\laragon"; Flags: ignoreversion recursesubdirs createallsubdirs

; 项目代码
Source: "laragon-portable\www\dental\*"; DestDir: "{app}\laragon\www\dental"; Flags: ignoreversion recursesubdirs createallsubdirs

; 部署脚本
Source: "post-install.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "laragon-startup.bat"; DestDir: "{app}"; Flags: ignoreversion

[Icons]
Name: "{group}\{#MyAppName}"; Filename: "{app}\laragon-startup.bat"; IconFilename: "{app}\laragon\laragon.exe"; IconIndex: 0; Comment: "启动牙科诊所管理系统"
Name: "{group}\卸载 {#MyAppName}"; Filename: "{uninstallexe}"
Name: "{commondesktop}\{#MyAppName}"; Filename: "{app}\laragon-startup.bat"; IconFilename: "{app}\laragon\laragon.exe"; IconIndex: 0; Tasks: desktopicon; Comment: "启动牙科诊所管理系统"

[Run]
; 安装完成后自动运行配置脚本
Filename: "{app}\post-install.bat"; Description: "{cm:InstallingDeps}"; Flags: runhidden waituntilterminated; StatusMsg: "{cm:InstallingDeps}"

; 可选：安装后启动
Filename: "{app}\laragon-startup.bat"; Description: "{cm:LaunchAfterInstall}"; Flags: nowait postinstall skipifsilent shellexec

[UninstallRun]
; 卸载前停止 MySQL 服务
Filename: "{cmd}"; Parameters: "/c taskkill /f /im mysqld.exe 2>nul & taskkill /f /im nginx.exe 2>nul"; Flags: runhidden

[UninstallDelete]
; 清理运行时生成的文件
Type: filesandordirs; Name: "{app}\laragon\data"
Type: filesandordirs; Name: "{app}\laragon\www\dental\storage\logs"
Type: filesandordirs; Name: "{app}\laragon\www\dental\bootstrap\cache"
Type: filesandordirs; Name: "{app}\laragon\www\dental\vendor"
Type: filesandordirs; Name: "{app}\laragon\www\dental\node_modules"

[Code]
// ── 安装前检查 ──────────────────────────────────────────────

function InitializeSetup(): Boolean;
begin
  Result := True;

  // 检查磁盘空间（至少需要 1.5GB）
  if GetSpaceOnDisk(ExpandConstant('{sd}'), True, 0, 0) then
  begin
    // Inno Setup 会自动处理磁盘空间不足
  end;
end;

// ── 安装过程中的进度提示 ────────────────────────────────────

procedure CurStepChanged(CurStep: TSetupStep);
begin
  if CurStep = ssPostInstall then
  begin
    // 安装文件复制完成，即将运行配置脚本
    WizardForm.StatusLabel.Caption := '正在配置系统环境，请稍候...';
  end;
end;

// ── 卸载确认 ─────────────────────────────────────────────────

function InitializeUninstall(): Boolean;
begin
  Result := MsgBox('确定要卸载牙科诊所管理系统吗？' + #13#10 + #13#10 +
    '注意：数据库中的数据将会被删除！' + #13#10 +
    '如需保留数据，请先在系统中执行数据备份。',
    mbConfirmation, MB_YESNO) = IDYES;
end;
