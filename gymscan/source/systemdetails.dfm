object SystemForm: TSystemForm
  Left = 360
  Top = 106
  Width = 832
  Height = 476
  Caption = 'SystemForm'
  Color = clBtnFace
  Font.Charset = DEFAULT_CHARSET
  Font.Color = clWindowText
  Font.Height = -11
  Font.Name = 'MS Sans Serif'
  Font.Style = []
  OldCreateOrder = False
  Position = poDesktopCenter
  OnCreate = FormCreate
  PixelsPerInch = 96
  TextHeight = 13
  object Label1: TLabel
    Left = 8
    Top = 188
    Width = 49
    Height = 13
    Caption = 'Atom URL'
  end
  object Label2: TLabel
    Left = 8
    Top = 252
    Width = 42
    Height = 13
    Caption = 'Atom DB'
  end
  object Button1: TButton
    Left = 28
    Top = 20
    Width = 89
    Height = 25
    Caption = 'Sync Upload'
    TabOrder = 0
    OnClick = Button1Click
  end
  object Button4: TButton
    Left = 28
    Top = 52
    Width = 89
    Height = 25
    Caption = 'Sync Doanlods'
    TabOrder = 1
    OnClick = Button4Click
  end
  object Button3: TButton
    Left = 28
    Top = 86
    Width = 137
    Height = 25
    Caption = 'Loop Sync'
    TabOrder = 2
    OnClick = Button3Click
  end
  object cbAutohide: TCheckBox
    Left = 28
    Top = 136
    Width = 97
    Height = 17
    Caption = 'Autohide'
    TabOrder = 3
  end
  object ProgressBar1: TProgressBar
    Left = 0
    Top = 420
    Width = 816
    Height = 17
    Align = alBottom
    TabOrder = 4
  end
  object Memo1: TMemo
    Left = 272
    Top = 0
    Width = 544
    Height = 420
    Align = alRight
    ScrollBars = ssBoth
    TabOrder = 5
  end
  object AtomEdit1: TEdit
    Left = 7
    Top = 204
    Width = 238
    Height = 21
    TabOrder = 6
    Text = 'http://www.matiesgym.rollout.co.za'
  end
  object Edit1: TEdit
    Left = 8
    Top = 269
    Width = 233
    Height = 21
    TabOrder = 7
  end
  object cbAutoStart: TCheckBox
    Left = 28
    Top = 156
    Width = 97
    Height = 17
    Caption = 'Autostart'
    TabOrder = 8
  end
  object FormStorage1: TFormStorage
    IniFileName = 'gymsync.ini'
    Options = []
    StoredProps.Strings = (
      'cbAutohide.Checked'
      'AtomEdit1.Text'
      'Edit1.Text'
      'cbAutoStart.Checked')
    StoredValues = <>
    Left = 164
    Top = 20
  end
end
