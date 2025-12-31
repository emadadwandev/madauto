curl -X PUT "https://apigateway-stg.careemdash.com/pos/api/v1/catalogs" \
  -H "Authorization: Bearer {ACCESS_TOKEN}" \
  -H "User-Agent: loyverse-integration/1.0" \
  -H "Brand-Id: {BRAND_ID}" \
  -H "Branch-Id: {BRANCH_ID}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "diff": false,
    "catalog": {
      "id": "catalog_5_main_branch",
      "name": "Careem menu",
      "include_tax": true,
      "tax": 5,
      "avg_price": 6.94,
      "file": null,
      "currency_id": 1,
      "category_ids": ["burrata", "folded", "flatbread"]
    },
    "categories": [
      {
        "id": "burrata",
        "name": "Burrata"
      },
      {
        "id": "folded",
        "name": "folded"
      },
      {
        "id": "flatbread",
        "name": "flatbread"
      }
    ],
    "sub_categories": [],
    "items": [
      {
        "id": "6",
        "name": "Burrata pistachio mortadella",
        "description": "",
        "price": 6.94,
        "currency": "AED",
        "category_id": "burrata",
        "sku": "Burrata 001",
        "sort_order": 1,
        "is_available": true,
        "is_active": true,
        "image_url": "http://madautomation.cloud/storage/menu-items/NXCVvl42qtS4I2A1zQDyZj15f5bMT7jiQBebeV0R.jpg",
        "tax_rate": 16,
        "modifier_group_ids": [4],
        "external_id": "691f11cb-6885-44e6-a8d6-4ff4968960d9"
      },
      {
        "id": "7",
        "name": "Roast beef folded",
        "description": "Roast beef folded",
        "price": 6.94,
        "currency": "AED",
        "category_id": "folded",
        "sku": "002",
        "sort_order": 2,
        "is_available": true,
        "is_active": true,
        "image_url": "http://madautomation.cloud/storage/menu-items/RvxJDYUbytFYDPAmR05tGoP2jzmvcrxXTviF1cEw.webp",
        "tax_rate": 16,
        "modifier_group_ids": [4],
        "external_id": "355eec92-10fa-4443-b90c-8476c11d583c"
      }
    ],
    "groups": [
      {
        "id": "4",
        "name": "extras",
        "description": "",
        "selection_type": "multiple",
        "is_required": false,
        "min_selections": 0,
        "max_selections": 10,
        "sort_order": 0,
        "modifiers": [
          {
            "id": "193",
            "name": "Extra on Flatbread",
            "description": "",
            "price_adjustment": 0,
            "sku": null,
            "is_active": true,
            "is_available": false,
            "sort_order": 0,
            "is_default": false
          },
          {
            "id": "202",
            "name": "Extra on Flatbread - DW SPECIAL BURRATA",
            "description": "",
            "price_adjustment": 1.85,
            "sku": null,
            "is_active": true,
            "is_available": false,
            "sort_order": 1,
            "is_default": false
          }
        ]
      }
    ],
    "options": [
      {
        "id": "193",
        "name": "Extra on Flatbread",
        "description": "",
        "price_adjustment": 0,
        "sku": null,
        "is_active": true,
        "is_available": false,
        "sort_order": 0,
        "is_default": false
      },
      {
        "id": "202",
        "name": "Extra on Flatbread - DW SPECIAL BURRATA",
        "description": "",
        "price_adjustment": 1.85,
        "sku": null,
        "is_active": true,
        "is_available": false,
        "sort_order": 1,
        "is_default": false
      }
    ]
  }'
{"id":14583136,"status":"pending","branch":{"name":"Abdoun Branch","id":"main_branch","brand_id":"DW","state":"MAPPED","created_at":"2025-12-13T16:20:45Z","updated_at":"2025-12-23T13:24:42Z","active":true,"catalog_id":null},"merchant_pay_type":"prepaid","delivery_type":"careem","notes":"","price":{"original_total_price":4.5,"merchant_discount_amount":0,"careem_discount_amount":0,"free_delivery_discount_value":0,"merchant_promo_amount":0,"careem_promo_amount":0,"tax_percentage":0,"total_taxable_price":14.65,"delivery_fee":10,"service_fee":0.15},"customer":{"name":"","phone_number":0,"address":{"name":"","number":"","location":{"lat":"","lng":""},"building":"","street":"","area":"","city":"","note":""},"payment_type":""},"captain":{"name":"","phone_number":"","eta":"2025-12-24T13:14:30Z"},"cash_in":0,"items":[{"id":"25","quantity":1,"delta_quantity":0,"event":null,"notes":"","unit_price":4.5,"item_price":4.5,"discount":0,"merchant_discount_amount":0,"careem_discount_amount":0,"total_price":4.5,"groups":[]}],"cancellation_reason":"","created_at":"2025-12-24T11:23:31Z","is_scheduled":false,"updated_at":"2025-12-24T11:23:31Z","metadata":{"order_instructions":{"merchant_notes":null,"merchant_instructions":null}},"delivery":{"type":"careem","delivery_mode":"on_demand"}}
