object ReportsDM: TReportsDM
  OldCreateOrder = False
  OnCreate = DataModuleCreate
  Left = 590
  Top = 186
  Height = 571
  Width = 448
  object LabelTable: TCDSADOTable
    CursorType = ctStatic
    TableName = 'Labels'
    ExportDelimiter = #0
    EnableRealtimeSync = False
    Left = 45
    Top = 190
  end
  object LabelDS: TDataSource
    DataSet = LabelTable
    Left = 113
    Top = 193
  end
  object ReportLinkTable: TCDSADOTable
    CursorType = ctStatic
    TableName = 'LinkedReports'
    ExportDelimiter = #0
    EnableRealtimeSync = False
    Left = 293
    Top = 78
  end
  object EmailTable: TCDSADOTable
    CursorType = ctStatic
    TableName = 'Email'
    ExportDelimiter = #0
    EnableRealtimeSync = False
    Left = 240
    Top = 16
  end
  object ReportsTable: TCDSADOQuery
    CursorType = ctStatic
    Parameters = <>
    SQL.Strings = (
      'select * from timesoftreports')
    AutoSQLStatement = False
    Left = 44
    Top = 263
  end
  object ReportsDataSource: TDataSource
    DataSet = ReportsTable
    Left = 141
    Top = 261
  end
  object CustomReportsTable: TCDSADOQuery
    CursorType = ctStatic
    Parameters = <>
    SQL.Strings = (
      'select * from timesoftcustomreports')
    AutoSQLStatement = False
    Left = 44
    Top = 327
  end
  object CustomReportsDS: TDataSource
    DataSet = CustomReportsTable
    Left = 153
    Top = 325
  end
  object QRDesignADOTableInterface1: TQRDesignADOTableInterface
    MenuTitle = 'ADO Table'
    ReadOnly = False
    CursorLocation = clUseClient
    Locktype = ltOptimistic
    DefaultConnectionOnly = False
    Left = 60
    Top = 124
  end
  object QRDesignADOQueryInterface1: TQRDesignADOQueryInterface
    MenuTitle = 'ADO Query'
    RequestLive = False
    UniDirectional = False
    CursorLocation = clUseClient
    Locktype = ltOptimistic
    DefaultConnectionOnly = False
    Left = 200
    Top = 100
  end
  object QuickReportsTable: TCDSADOTable
    CursorType = ctStatic
    TableName = 'Quickreports'
    ExportDelimiter = #0
    EnableRealtimeSync = False
    Left = 221
    Top = 174
  end
  object QuickReportsDS: TDataSource
    DataSet = QuickReportsTable
    Left = 313
    Top = 173
  end
  object QRDesignBDETableInterface1: TQRDesignBDETableInterface
    MenuTitle = 'BDE Table'
    ReadOnly = False
    DatabaseName = 'Timesoft'
    DefaultDatabaseOnly = False
    Left = 272
    Top = 308
  end
  object QRDesignBDEQueryInterface1: TQRDesignBDEQueryInterface
    MenuTitle = 'BDE Query'
    RequestLive = False
    UniDirectional = False
    DatabaseName = 'Timesoft'
    DefaultDatabaseOnly = False
    Left = 328
    Top = 264
  end
  object ReportDesignerDialog1: TReportDesignerDialog
    AfterReportFormCreated = ReportDesignerDialog1AfterReportFormCreated
    BeforeOpenDataset = ReportDesignerDialog1BeforeOpenDataset
    LabelSettings.FirstLabel = 0
    LabelSettings.LabelCount = 0
    ShowFilterBeforePrint = False
    UseDataModules = False
    UseCurrentDatasets = False
    SaveLoadPrinterSetup = False
    ScriptsEnabled = True
    PrepareAutomatically = False
    PrintIfEmpty = True
    PrinterSettings.Copies = 1
    PrinterSettings.OutputBin = First
    PrinterSettings.Duplex = False
    PrinterSettings.FirstPage = 0
    PrinterSettings.LastPage = 0
    UsePrinterSettings = False
    UseModalPreview = False
    Version = '1.59.0'
    UserSettings = [AllowSQLEdit, AllowDatasetEdit, AllowBlockEdit, AllowScriptEdit]
    EditorSettings = [UndoEnabled, AutoEditAfterInsert, ShowDatafieldListbox, DatafieldsSorted]
    SQLSettings.DelimiterType = delCustom
    DefaultFileExtension = '.QR2'
    DefaultFileFilter = 'Reports|*.QR2'
    NewReportGridSpanPages = False
    Left = 44
    Top = 72
  end
  object CDSQRDParamEditor1: TCDSQRDParamEditor
    Caption = 'Report Parameters'
    LeftCoOrd = 8
    QRDesignerDialog = ReportDesignerDialog1
    Spacing = 44
    TopCoOrd = 8
    Left = 136
    Top = 16
  end
  object NotificationReports: TCDSADOQuery
    CursorType = ctStatic
    Parameters = <>
    SQL.Strings = (
      'select * from NotificationReports')
    AutoSQLStatement = False
    Left = 296
    Top = 379
  end
  object ReportParametersTable: TCDSADOQuery
    CursorType = ctStatic
    Parameters = <>
    SQL.Strings = (
      'select * from reportparameters')
    AutoSQLStatement = False
    Left = 56
    Top = 403
  end
end
