{***************************************************************
*
* Project Name      : timesoft
* Project Group Dir : Unknown
* Project Group Name: Timesoft
* Project Dir       : C:\Projects\Timesoft
* Unit Name         : ReportsDataModule
* Purpose           : All tables, components to do with reporting, email, corresp etc.
* Author            : CDS
* History           :
*
****************************************************************}

unit ReportsDataModule;

interface

uses
  Windows, Messages, SysUtils, Classes, Graphics, Controls, Forms, Dialogs,
  CDSQRDParamEditor, qrddlgs, db, dbTables, RxMemDS, CDSTable, QRDEsign,
  qrpDatasetInterface, qrdDatasetInterface_BDE, qrdDatasetInterface_ADO,
  ADODB, CDSADOTable, CDSADOQuery, kbmMemTable;

type
  TReportsDM = class(TDataModule)
    ReportLinkTable: TCDSADOTable;
    EmailTable: TCDSADOTable;
    ReportsTable: TCDSADOQuery;
    ReportsDataSource: TDataSource;
    LabelTable: TCDSADOTable;
    LabelDS: TDataSource;
    CustomReportsTable: TCDSADOQuery;
    CustomReportsDS: TDataSource;
    QRDesignADOTableInterface1: TQRDesignADOTableInterface;
    QRDesignADOQueryInterface1: TQRDesignADOQueryInterface;
    QuickReportsTable: TCDSADOTable;
    QuickReportsDS: TDataSource;
    QRDesignBDETableInterface1: TQRDesignBDETableInterface;
    QRDesignBDEQueryInterface1: TQRDesignBDEQueryInterface;
    ReportDesignerDialog1: TReportDesignerDialog;
    CDSQRDParamEditor1: TCDSQRDParamEditor;
    NotificationReports: TCDSADOQuery;
    ReportParametersTable: TCDSADOQuery;
    procedure ReportDesignerDialog1BeforeOpenDataset(DS: TDataSet);
    procedure ReportDesignerDialog1AfterReportFormCreated(Form: TForm;
      QRD: TComponent);
    procedure DataModuleCreate(Sender: TObject);
  private
    { Private declarations }
  public
    procedure QuickRepPreview(Sender: TObject);
    { Public declarations }
  end;

var
  ReportsDM: TReportsDM;

implementation

uses CDSDB, PrintPreview, QRPrntr, cdsutils;


{$R *.DFM}

procedure TReportsDM.ReportDesignerDialog1BeforeOpenDataset(DS: TDataSet);
var ipos : Integer;
    sSQl : string;

  procedure SetADOFilter(Dataset:Tdataset);
  begin
    TADOQuery(DS).Filter   := dataset.Filter;
    TADOQuery(DS).Filtered := Dataset.Filtered;
  end;

  procedure SetQuery(Field:String;Value:String);
  var
    iPos : Integer;
    sSQL : string;
  begin
    with TADOQuery(DS) do
    try
      DS.Filter := '';
      Active := false;
      iPos := Pos('WHERE',sql.Text);
      if iPos > 0 then
      begin
        sSQL := Copy(sql.Text,1,iPos-1);
        SQL.Clear;
        sql.Text :=  sSQL+' WHERE '+Name+'.['+Field+'] = '+Value;
      end
      else
        sql.Text :=  sql.Text+' WHERE '+Name+'.['+Field+'] = '+Value;

      Active := True;
    except
    end;
  end;


  procedure SetAlternateQuery(Field:String;Value:String;TableNme : string);
  var
    iPos : Integer;
    sSQL : string;
  begin
    with TADOQuery(DS) do
    try
      DS.Filter := '';
      Active := false;
      iPos := Pos('WHERE',sql.Text);
      if iPos > 0 then
      begin
        sSQL := Copy(sql.Text,1,iPos-1);
        SQL.Clear;
        sql.Text :=  sSQL+' WHERE '+TableNme+'.['+Field+'] = '+Value;
      end
      else
        sql.Text :=  sql.Text+' WHERE '+TableNme+'.['+Field+'] = '+Value;

      Active := True;
    except
    end;
  end;


begin
end;


procedure TReportsDM.QuickRepPreview(Sender: TObject);
var
  PrevForm : TPreviewForm;
begin
  PrevForm:=TPreviewForm.Create(Nil);
  PrevForm.QRPreview1.QRPrinter := TQRPrinter(Sender);
  CDS_UpdateEmailAddressInReportPreview(PrevForm, ReportDesignerDialog1.SessionName, ReportDesignerDialog1.HelpFile, efPreview);
end;

procedure TReportsDM.ReportDesignerDialog1AfterReportFormCreated(
  Form: TForm; QRD: TComponent);
begin
  TQRDLoader(QRD).QReport.OnPreview:=QuickRepPreview;
   TQRDLoader(QRD).QReport.ShowProgress := True;
end;


procedure TReportsDM.DataModuleCreate(Sender: TObject);
begin
  ReportsTable.TableName       := 'timesoftreports';
  CustomReportsTable.TableName := 'timesoftcustomreports';
end;

end.
