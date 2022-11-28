unit addeditperson;

interface

uses
  Windows, Messages, SysUtils, Variants, Classes, Graphics, Controls, Forms,
  Dialogs, StdCtrls, DBCtrls, Mask, Buttons, ExtCtrls, ToolEdit, RXDBCtrl,
  CDSLookupDBEdit, RxDBComb;

type
  TAddEditPersonForm = class(TForm)
    Label3: TLabel;
    Label7: TLabel;
    DBEdit1: TDBEdit;
    Panel1: TPanel;
    BitBtn5: TBitBtn;
    BitBtn1: TBitBtn;
    Label4: TLabel;
    DBDateEdit1: TDBDateEdit;
    Label1: TLabel;
    DBEdit3: TDBEdit;
    Label2: TLabel;
    DBEdit6: TDBEdit;
    Label5: TLabel;
    DBEdit7: TDBEdit;
    Label6: TLabel;
    DBDateEdit2: TDBDateEdit;
    RxDBComboBox1: TRxDBComboBox;
    procedure BitBtn1Click(Sender: TObject);
    procedure BitBtn5Click(Sender: TObject);
  private
    { Private declarations }
  public
    { Public declarations }
  end;

var
  AddEditPersonForm: TAddEditPersonForm;

implementation

{$R *.dfm}

procedure TAddEditPersonForm.BitBtn1Click(Sender: TObject);
begin
  AddEditPersonForm.modalresult := mrCancel;
end;

procedure TAddEditPersonForm.BitBtn5Click(Sender: TObject);
begin
   if (dbedit1.Text = '') or    (dbedit3.Text = '') or  (dbedit6.Text = '') or (dbedit7.Text = '') then
   begin
     Showmessage ('Please fill all fields in..');
     AddEditPersonForm.Modalresult := mrNone;
   end;


end;

end.


