object HttpPostForm: THttpPostForm
  Left = 180
  Top = 70
  Width = 891
  Height = 565
  Caption = 'ATOM API'
  Color = clBtnFace
  Font.Charset = DEFAULT_CHARSET
  Font.Color = clWindowText
  Font.Height = -11
  Font.Name = 'MS Sans Serif'
  Font.Style = []
  OldCreateOrder = True
  PixelsPerInch = 96
  TextHeight = 13
  object ToolsPanel: TPanel
    Left = 0
    Top = 0
    Width = 875
    Height = 201
    Align = alTop
    TabOrder = 0
    object Label3: TLabel
      Left = 8
      Top = 23
      Width = 51
      Height = 13
      Caption = 'Enroll URL'
    end
    object Label1: TLabel
      Left = 8
      Top = 87
      Width = 42
      Height = 13
      Caption = 'Get URL'
    end
    object Label2: TLabel
      Left = 8
      Top = 139
      Width = 47
      Height = 13
      Caption = 'PersonNo'
    end
    object Label4: TLabel
      Left = 8
      Top = 167
      Width = 44
      Height = 13
      Caption = 'Add URL'
    end
    object ActionURLEdit: TEdit
      Left = 72
      Top = 19
      Width = 93
      Height = 21
      TabOrder = 0
      Text = '999'
    end
    object PostButton: TButton
      Left = 69
      Top = 45
      Width = 75
      Height = 21
      Caption = '&Enroll'
      TabOrder = 1
      OnClick = PostButtonClick
    end
    object GetUserURL: TEdit
      Left = 72
      Top = 83
      Width = 289
      Height = 21
      TabOrder = 2
      Text = '999'
    end
    object Button1: TButton
      Left = 73
      Top = 105
      Width = 75
      Height = 21
      Caption = '&Get'
      TabOrder = 3
      OnClick = Button1Click
    end
    object UpdatePersonEdit: TEdit
      Left = 72
      Top = 135
      Width = 45
      Height = 21
      TabOrder = 4
      Text = '999'
    end
    object Button2: TButton
      Left = 72
      Top = 134
      Width = 75
      Height = 21
      Caption = '&Update'
      TabOrder = 5
      OnClick = Button2Click
    end
    object PersonBody: TMemo
      Left = 380
      Top = 22
      Width = 473
      Height = 130
      Lines.Strings = (
        
          '{"PersonID":1,"Person_Name":"NewName","Person_Surname":"NewSurna' +
          'me","Person_Number":"999","DepartmentID":1,"PersonTypeID":1,"Per' +
          'sonStateID":1,"Start_Date":"2014-02-14T00:00:00","Termination_Da' +
          'te":"1999-09-17T00:00:00","ID_Number":"","Designation":"Director' +
          '","Access_Group_List":["1"],"ThirdPartyUID":0}')
      ScrollBars = ssBoth
      TabOrder = 6
      WordWrap = False
    end
    object AddPersonEdit: TEdit
      Left = 76
      Top = 163
      Width = 33
      Height = 21
      TabOrder = 7
      Text = '999'
    end
    object Button3: TButton
      Left = 72
      Top = 162
      Width = 75
      Height = 21
      Caption = '&Add'
      TabOrder = 8
      OnClick = Button3Click
    end
  end
  object DisplayMemo: TMemo
    Left = 0
    Top = 201
    Width = 875
    Height = 325
    Align = alClient
    Font.Charset = ANSI_CHARSET
    Font.Color = clWindowText
    Font.Height = -11
    Font.Name = 'Courier New'
    Font.Style = []
    ParentFont = False
    ScrollBars = ssBoth
    TabOrder = 1
  end
end
