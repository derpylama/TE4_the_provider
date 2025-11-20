***
# Create blog

**Endpoint:** `/api/blog-api/create-blog.php`  
**Method:** `POST`

## Description
Creates a new blog if the user does not already has one

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| content | string | yes | The text content of the blog |
| title  | string | yes | The title of the created blog |
| token | string | yes | The auth token for the desierd acount |

## Example JSON Return

```json

```


---

# Get blog

**Endpoint:** `/api/blog-api/get-blog.php`  
**Method:** `GET`

## Description
Either gets a specifik blog or all blogs that exists within a orginasation

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| Token | strin | yes | Auth token |
| blogId | int | no | Used to get a specifik blog |

## Example JSON Return

```json
[
    {
        "id": 1,
        "content": "skjhfksdj",
        "title": "test",
        "user_id": 1,
        "creation_date": "2025-11-19 11:35:36",
        "latest_update": "2025-11-19 11:35:36",
        "customer_id": 0
    }
]
```
