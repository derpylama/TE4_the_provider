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

# Create blog

**Endpoint:** `/blog-api/create-blog.php`  
**Method:** `POST`

## Description
Creates a wiki for the specifik user if they dont already have one.

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes | auth token |
| title | string | yes | New title for the blog |
| content | string | yes | New content for the blog |
| general | string | no | New general data for the blog |

## Example JSON Return

```json
{
    "status": "success",
    "message": "blog created",
    "blog_id": "4"
}
```



---

# Edit blog

**Endpoint:** `/blog-api/edit-blog.php`  
**Method:** `POST`

## Description
Edit the content, title and general data for blog

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes | auth token |
| title | string | no | New title for the blog |
| content | string | no | New content for the blog |
| general | string | no | New general data for the blog |
| userId | int | no | If the current user is admin this allows them to edit another users blog |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Blog updated successfully"
}
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

---


---

# Delete

**Endpoint:** `/api/blog-api/delete-blog.php`  
**Method:** `POST`

## Description
Allows a user to delete its own blog or a admin to delete another user's blog. It is needed to either send content and title or either one on its own.

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes | auth token |
| userId | int | no | The id of the user that the admin wants to edit a blog for |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Blog deleted successfully"
}
```
