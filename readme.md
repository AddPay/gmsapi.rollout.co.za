# GMS API SPECS

## POST /api/gymsync.php

#### Options

| Name | Description                                                                                                                                                                                                              | Required            | Type   | Default |
|------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------------|--------|---------|
| sk   | ATOM secret key                                                                                                                                                                                                          | Yes                 | string | ''      |
| sa   | **Sync Action**<br><br>down: get records from the Persons table on GMS that have changed<br><br>update: notify GMS that the records have been updated on GymSync<br><br>up: update table data on GMS with data from ATOM | Yes                 | string | up      |
| da   | Data: data from ATOM that must be updated on GMS. Only applicable for 'up'. See example format below. | Only for sa = up        | string | ''      |
| ids  | GMS User IDs. These users' data has been updated on ATOM with the data provided by GMS. Reset the updated flag so they are not resent to gymsync. Only applicable for update.                                                                        | Only for sa = update | string | 0       |
| wc   | Where columns: the identifier column that tell the api whether to update or insert the data from the up action. Only applicable for up.                                                                                                         | Only for sa = up     | string | ''      |

Example 'da':

```
{  
    "Transactions": [{
        "TransactionID": "1",
        "tDateTime": "2015-11-02 11:16:50",
        "PersonID": "1",
        "ReaderID": "1",
        "tDirection": "OUT",
        "tReaderDescription": "Maties Gym Entrance",
        "tManual": "0",
        "tDeleted": "0",
        "tTAProcessed": "0",
        "TimesheetDayID": "0",
        "tExtProcessed": "0",
        "tLogical": "0"
    }]
}
```

## POST /api/atom.php

#### Options

| Name | Description | Required | Type | Default |
|---|---|---|---|---|
| action | The sync action to perform. See actions below. | Yes | string | geteditusers |
| actiontype | For 'updateusers' it is the comma-separated suspi_users.id's of all users that have been updated on ATOM. For 'resendusers', it is the suspi_users.PersonID of the users to update on ATOM. | No | string | '' |

#### Actions:

**getstatus**

Get the ATOM user that needs to be enrolled according to the GMS. The response is a comma-separated string: first part being whether the user was edited on GMS, second part is the pPersonNumber to enroll, and third is the site id. Eg. 1,GMS0000001,1

**geteditusers**

Get the users that have been edited on GMS that now need to be updated on ATOM. The user's data returns in JSON format for GymSync to process.

**clearstatus**

Set the GMS User as enrolled on ATOM. Naturally, this should happen after enrollment - you don't want to create an endless loop of enrolling the same user.

**updateusers**

Set the users as having been successfully updated on ATOM.

**resenduser**

Set the users as needing to be updated on ATOM.