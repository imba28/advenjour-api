# MMP3 Backend API

Dokumentation: http://advenjour.ddns.net/api/doc

Die API orientiert sich am JSON API Standard 1.0 https://jsonapi.org/

Es gibt ResourceIdentifier und SingleResources. Ein ResourceIdentifier setzt sich aus `id` und `type` zusammen.

```json
{
  "data": {
    "id": 4,
    "type": "Event"
  }
}
```

Eine SingleResource beinhaltet detailiertere Informationen über das Objekt.

```json
{
  "data": {
    "id": 4,
    "type": "Event",
    "attributes": {
      "name": "Test Event 2",
      "description": "<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>\n",
      "price": {
        "value": "42",
        "unit": "€"
      },
      "date": {
        "from": null,
        "to": null
      }
    },
    "relationships": {
      "images": [
        {
          "id": 16,
          "type": "Image"
        },
        {
          "id": 13,
          "type": "Image"
        }
      ]
    }
  }
}
```
Alle Daten befinden sich immer in `data`. 

Die Verknüpfungen der Objekte (1:n, n:m, 1:1) befinden sich in `relationships`. Sie können zum Response hinzugefügt werden, indem der Name der Property im Request Query Parameter `include` eingetragen wird.
z.B.: `http://advenjour.ddns.net/api/v1/category/20.json?include=image`

Es können auch mehrere Verknüpfungen hinzugefügt werden `z.B.: `http://advenjour.ddns.net/api/v1/category/20.json?include=image,parentCategory`

```json
{
  "data": {
    "id": 20,
    "type": "EventCategory",
    "attributes": {
      "name": "Natur"
    },
    "relationships": {
      "image": {
        "id": 16,
        "type": "Image"
      }
    },
    "included": [
      {
        "id": 16,
        "type": "Image",
        "attributes": {
          "fullPath": "http://advenjour.ddns.net/eventCategories/abandoned-forest-hd-wallpaper-34950.jpg",
          "title": null,
          "description": null,
          "thumbnails": [
            "http://advenjour.ddns.net/eventCategories/image-thumb__16__eventCategoryOverview/abandoned-forest-hd-wallpaper-34950.jpeg"
          ]
        }
      }
    ]
  }
}
```

Fehler:
```json
{
  "errors": [
    {
      "status": 403,
      "title": "auth.login.errors.invalid_credentials"
    }
  ]
}
```

```json
{
  "errors": [
    {
      "status": 422,
      "title": "auth.register.errors.username"
    },
    {
      "status": 422,
      "title": "auth.register.errors.email"
    },
    {
      "status": 422,
      "title": "auth.register.errors.password"
    }
  ]
}
```