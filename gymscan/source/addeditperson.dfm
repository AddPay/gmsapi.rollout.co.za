object AddEditPersonForm: TAddEditPersonForm
  Left = 458
  Top = 155
  Width = 344
  Height = 380
  Caption = 'Member Details'
  Color = clBtnFace
  Font.Charset = DEFAULT_CHARSET
  Font.Color = clWindowText
  Font.Height = -11
  Font.Name = 'MS Sans Serif'
  Font.Style = []
  OldCreateOrder = False
  Position = poScreenCenter
  PixelsPerInch = 96
  TextHeight = 13
  object Label3: TLabel
    Left = 55
    Top = 53
    Width = 28
    Height = 13
    Caption = 'Name'
  end
  object Label7: TLabel
    Left = 14
    Top = 234
    Width = 66
    Height = 13
    Caption = 'Person Status'
  end
  object Label4: TLabel
    Left = 40
    Top = 172
    Width = 48
    Height = 13
    Caption = 'Start Date'
  end
  object Label1: TLabel
    Left = 39
    Top = 80
    Width = 42
    Height = 13
    Caption = 'Surname'
  end
  object Label2: TLabel
    Left = 18
    Top = 107
    Width = 73
    Height = 13
    Caption = 'Person Number'
  end
  object Label5: TLabel
    Left = 38
    Top = 134
    Width = 51
    Height = 13
    Caption = 'ID Number'
  end
  object Label6: TLabel
    Left = 40
    Top = 196
    Width = 45
    Height = 13
    Caption = 'End Date'
  end
  object DBEdit1: TDBEdit
    Left = 102
    Top = 52
    Width = 169
    Height = 21
    DataField = 'pName'
    DataSource = MainMenuForm.PersonDS
    Font.Charset = DEFAULT_CHARSET
    Font.Color = clWindowText
    Font.Height = -11
    Font.Name = 'MS Sans Serif'
    Font.Style = []
    ParentFont = False
    TabOrder = 0
  end
  object Panel1: TPanel
    Left = 0
    Top = 308
    Width = 328
    Height = 33
    Align = alBottom
    TabOrder = 1
    DesignSize = (
      328
      33)
    object BitBtn5: TBitBtn
      Left = 244
      Top = 4
      Width = 79
      Height = 25
      Anchors = [akRight]
      Caption = 'Accept'
      TabOrder = 0
      OnClick = BitBtn5Click
      Kind = bkOK
    end
    object BitBtn1: TBitBtn
      Left = 160
      Top = 4
      Width = 79
      Height = 25
      Anchors = [akRight]
      TabOrder = 1
      OnClick = BitBtn1Click
      Kind = bkCancel
    end
  end
  object DBDateEdit1: TDBDateEdit
    Left = 102
    Top = 166
    Width = 164
    Height = 21
    DataField = 'pStartDate'
    DataSource = MainMenuForm.PersonDS
    NumGlyphs = 2
    TabOrder = 2
  end
  object DBEdit3: TDBEdit
    Left = 101
    Top = 77
    Width = 169
    Height = 21
    DataField = 'pSurname'
    DataSource = MainMenuForm.PersonDS
    Font.Charset = DEFAULT_CHARSET
    Font.Color = clWindowText
    Font.Height = -11
    Font.Name = 'MS Sans Serif'
    Font.Style = []
    ParentFont = False
    TabOrder = 3
  end
  object DBEdit6: TDBEdit
    Left = 101
    Top = 106
    Width = 169
    Height = 21
    DataField = 'pPersonNumber'
    DataSource = MainMenuForm.PersonDS
    Font.Charset = DEFAULT_CHARSET
    Font.Color = clWindowText
    Font.Height = -11
    Font.Name = 'MS Sans Serif'
    Font.Style = []
    ParentFont = False
    TabOrder = 4
  end
  object DBEdit7: TDBEdit
    Left = 100
    Top = 131
    Width = 169
    Height = 21
    DataField = 'pIDNo'
    DataSource = MainMenuForm.PersonDS
    Font.Charset = DEFAULT_CHARSET
    Font.Color = clWindowText
    Font.Height = -11
    Font.Name = 'MS Sans Serif'
    Font.Style = []
    ParentFont = False
    TabOrder = 5
  end
  object DBDateEdit2: TDBDateEdit
    Left = 102
    Top = 190
    Width = 164
    Height = 21
    DataField = 'pTerminationDate'
    DataSource = MainMenuForm.PersonDS
    NumGlyphs = 2
    TabOrder = 6
  end
  object RxDBComboBox1: TRxDBComboBox
    Left = 104
    Top = 232
    Width = 145
    Height = 21
    Style = csDropDownList
    DataField = 'PersonState'
    DataSource = MainMenuForm.PersonDS
    EnableValues = True
    ItemHeight = 13
    Items.Strings = (
      'Active'
      'Expired'
      'Deleted')
    TabOrder = 7
    Values.Strings = (
      '1'
      '2'
      '3')
  end
end
