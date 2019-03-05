# sell-hive website

Sell Hive was developed to improve the cash point on board game flea market of Bremer Spieletage.
Multiple sellers are able to add their items (in our case board games) on this website.
On an event (in our case Bremer Spieletage) every seller can bring in their items.
All items can paid on a single cash point with an electronic register.

## Service Description

### Cashpoint Connection

{secret} is a preshared secret which must be set for every call to cashpoint connection service.

#### Export all sellers

Call (GET):
```
<base url>/cashpoint/export/sellers/{secret}
```

Result:
```
[
{
	"id" : <seller id>,
	"barcode_id" : "<prefix><seller id>,
	"name" : "<seller name>"
},
{
	"id" : <seller id>,
	"barcode_id" : "<prefix><seller id>",
	"name" : "<seller name>"
}
...
]
```

#### Export all items

Call (GET):
```
<base url>/cashpoint/export/items/{secret}
```

Result:
```
[
{
	"id" : <item id>,
	"barcode_id" : "<prefix><item id>",
	"seller" : <seller id>,
	"name" : "<item name>",
	"price" : <item price in cent>,
	"boxed_as_new" : <true or false>,
	"comment" : "<item comment>",
	"labeled" : <true or false>,
	"transfered" : <true or false>,
	"sold" : <true or false>,
	"state" : "<created or labeled or transfered or sold>"
},
{
	"id" : <item id>,
	"barcode_id" : "<prefix><item id>",
	"seller" : <seller id>,
	"name" : "<item name>",
	"price" : <item price in cent>,
	"boxed_as_new" : <true or false>,
	"comment" : "<item comment>",
	"labeled" : <true or false>,
	"transfered" : <true or false>,
	"sold" : <true or false>,
	"state" : "<created or labeled or transfered or sold>"
}
...
]
```

#### Confirm that items arrived on flea market

Call (POST):
```
<base url>/cashpoint/confirm/transfer/{secret}
```

POST data:
```
{
	"item_ids" : [<item id 1>, <item id 2>, ..., <item id n>]
}
```

Result:<br>
HTTP Status 200 without data

#### Confirm that items are sold

Call (POST):
```
<base url>/cashpoint/confirm/sold/{secret}
```

POST data:
```
{
	"item_ids" : [<item id 1>, <item id 2>, ..., <item id n>]
}
```

Result:<br>
HTTP Status 200 without data

## Prepare for Development

You need composer to prepare this component project:
https://getcomposer.org/download/

Rest should be done by gradle task ```build```.
