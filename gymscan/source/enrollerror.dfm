object EnrollErrorForm: TEnrollErrorForm
  Left = 502
  Top = 206
  Width = 458
  Height = 203
  BorderIcons = [biSystemMenu]
  Caption = 'Error'
  Color = clBtnFace
  Font.Charset = DEFAULT_CHARSET
  Font.Color = clWindowText
  Font.Height = -11
  Font.Name = 'MS Sans Serif'
  Font.Style = []
  FormStyle = fsStayOnTop
  OldCreateOrder = False
  Position = poDesktopCenter
  PixelsPerInch = 96
  TextHeight = 13
  object Label1: TLabel
    Left = 21
    Top = 16
    Width = 405
    Height = 73
    Alignment = taCenter
    Caption = 'Could not locate member for enrollment'
    Font.Charset = DEFAULT_CHARSET
    Font.Color = clWindowText
    Font.Height = -24
    Font.Name = 'MS Sans Serif'
    Font.Style = []
    ParentFont = False
    WordWrap = True
  end
  object EnrollErrorButton: TBitBtn
    Left = 188
    Top = 112
    Width = 75
    Height = 25
    TabOrder = 0
    Kind = bkOK
  end
end
