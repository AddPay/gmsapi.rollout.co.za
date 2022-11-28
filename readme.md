# GMS API SPECS

### POST /api/gymsync.php

Options

| Name | Description                                                                                                                                                                                                              | Required            | Type   | Default |
|------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------------|--------|---------|
| sk   | ATOM secret key                                                                                                                                                                                                          | Yes                 | string | ''      |
| sa   | **Sync Action**<br><br>down: get records from the Persons table on GMS that have changed<br><br>update: notify GMS that the records have been updated on GymSync<br><br>up: update table data on GMS with data from ATOM | Yes                 | string | up      |
| da   | Data: data from ATOM that must be updated on GMS. The format is like so:<br><br><pre>{  "Transactions": [{ <br>   "TransactionID": "1",<br>   "tDateTime": "2015-11-02 11:16:50",<br>   "PersonID": "1",<br>   "ReaderID": "1",<br>   "tDirection": "OUT",<br>   "tReaderDescription": "Maties Gym Entrance",<br>   "tManual": "0",<br>   "tDeleted": "0",<br>   "tTAProcessed": "0",<br>   "TimesheetDayID": "0",<br>   "tExtProcessed": "0",<br>   "tLogical": "0"<br>}]} </pre>                                                                                                       | Yes, sa = up        | string | ''      |
| ids  | GMS User IDs. These users' data has been updated on ATOM with the data provided by GMS. Reset the updated flag so they are not resent to gymsync.                                                                        | Yes, if sa = update | string | 0       |
| wc   | Where columns: the identifier column that tell the api whether to update or insert the data from the up action.                                                                                                          | Yes, if sa = up     | string | ''      |

### POST /api/atom.php

Options

| Name | Description | Required | Type | Default |
|---|---|---|---|---|
| action | The sync action to perform:<br><br>- getstatus: Get the ATOM user that needs to be enrolled according to the GMS<br>- geteditusers: Get the users that have been edited on GMS that now need to be synced down to ATOM<br>- clearstatus: Remove the ATOM user set to be enrolled from the GMS DB (typically happens after enrollment, because you don't want to enroll again | Yes | string | geteditusers |
| actiontype | Depends on the action. For 'clearstatus' it is the status field that must be cleared (eg get_enroll). Not required for the other actions |  | string | '' |