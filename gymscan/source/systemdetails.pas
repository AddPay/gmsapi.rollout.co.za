unit systemdetails;

interface

uses
  Windows, Messages, SysUtils, Variants, Classes, Graphics, Controls, Forms,
  Dialogs, StdCtrls, ComCtrls, Placemnt;

type
  TSystemForm = class(TForm)
    Button1: TButton;
    Button4: TButton;
    Button3: TButton;
    cbAutohide: TCheckBox;
    ProgressBar1: TProgressBar;
    Memo1: TMemo;
    FormStorage1: TFormStorage;
    AtomEdit1: TEdit;
    Label1: TLabel;
    Edit1: TEdit;
    Label2: TLabel;
    cbAutoStart: TCheckBox;
    procedure Button3Click(Sender: TObject);
    procedure Button1Click(Sender: TObject);
    procedure Button4Click(Sender: TObject);
    procedure FormCreate(Sender: TObject);
  private
    { Private declarations }
  public
    { Public declarations }
  end;

var
  SystemForm: TSystemForm;

implementation

uses gymsyncmain;

{$R *.dfm}

procedure TSystemForm.Button3Click(Sender: TObject);
begin
  MainMenuForm.Button3Click(Sender);
end;

procedure TSystemForm.Button1Click(Sender: TObject);
begin
  MainMenuForm.Button1Click(Sender);
end;

procedure TSystemForm.Button4Click(Sender: TObject);
begin
  MainMenuForm.Button4Click(Sender);
end;

procedure TSystemForm.FormCreate(Sender: TObject);
begin
  SystemForm.formstorage1.RestoreFormPlacement;
  mainmenuform.timer1.Enabled := SystemForm.cbAutohide.checked;
end;

end.
