program gymscan;

{%File 'gymsynctables.ini'}

uses
  Forms,
  gymsyncmain in 'gymsyncmain.pas' {MainMenuForm},
  systemdetails in 'systemdetails.pas' {SystemForm},
  ReportsDataModule in 'ReportsDataModule.pas' {ReportsDM: TDataModule},
  TablesDataModule in 'TablesDataModule.pas' {TablesDM: TDataModule},
  addeditperson in 'addeditperson.pas' {AddEditPersonForm},
  atomwebservice in 'atomwebservice.pas' {HttpPostForm},
  uLkJSON in 'uLkJSON.pas',
  enrollerror in 'enrollerror.pas' {EnrollErrorForm};

{$R *.res}

begin
  Application.Initialize;
  Application.CreateForm(TMainMenuForm, MainMenuForm);
  Application.CreateForm(TSystemForm, SystemForm);
  Application.CreateForm(TReportsDM, ReportsDM);
  Application.CreateForm(TTablesDM, TablesDM);
  Application.CreateForm(TAddEditPersonForm, AddEditPersonForm);
  Application.CreateForm(THttpPostForm, HttpPostForm);
  Application.CreateForm(TEnrollErrorForm, EnrollErrorForm);
  Application.Run;
end.
