USE [ATOM]
GO
/****** Object:  Trigger [dbo].[ResetEditFlag]    Script Date: 15/03/2016 01:36:14 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
ALTER TRIGGER [dbo].[ResetEditFlag] ON [dbo].[Persons]
AFTER UPDATE
AS
BEGIN
 IF NOT UPDATE (p3rdPartyUID) 
    BEGIN
		UPDATE Persons
			SET p3rdPartyUID = 0
			FROM inserted
			WHERE (Persons.PersonID = inserted.PersonID) 
	END
				
END
