***

# Create Wiki

**Endpoint:** `/api/wiki-api/create-wiki.php`  
**Method:** `POST`

## Description
Creates a wiki for the current user.
every user can only have 1 wiki but multiple articles in a wiki

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| title | string | yes | Title of the created wiki |
| description | string | no | description of the wiki. ex what it contains |
| general | array | no | General ex metadata to be stored with the wiki |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Wiki successfully created.",
    "data": {}
}
```
---

# Create Wiki article

**Endpoint:** `/api/wiki-api/create-wiki-article.php`  
**Method:** `POST`

## Description
Creates a wiki article for the user

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| title | string | yes | title of the created article |
| content | string | no | content of the article ex json encoded html or just a string |
| general | array | no | ex metadata for the article  |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Article created successfully",
    "data": {
        "wiki_id": 6,
        "wiki_article_id": 6,
        "title": "Test Wiki"
    }
}
```
---

# Get Wikis

**Endpoint:** `/api/wiki-api/get-wiki.php`  
**Method:** `GET`

## Description
Gets all titel and description of the wikis from the same company
or the titel and description for the ones matching the search

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| search_query | string | no | what to search for |
| search_filter | array | no | Where to search. defualts to ["title"] but can include "title", "description"  or can include both |
| amount | int | no | how many you can get back. defualt 10 |
| offset | int | no | At what index the returned results start |
| order_direction | string enum ["DESC", "ASC"] | no | which order the list is returned. defualt DESC which is newest -> oldest |

## Example JSON Return

```json
{
"status": "success",
"message": "Fetched wikis",
"data": {
	"wikis": [
		{
			"id": 6,
			"title": "Test Wiki",
			"description": "Example description for wiki",
			"creation_date": "2025-12-09 21:32:06"
		},
		{
			"id": 5,
			"title": "Test Wiki",
			"description": "Example description for wiki",
			"creation_date": "2025-12-09 21:32:03"
		},

	],
	"total_count": 2,
	"offset": 0,
	"amount": 10
}
```
---

# Get Wiki Article

**Endpoint:** `/api/wiki-api/get-wiki-article.php`  
**Method:** `GET`

## Description
GETS either a wiki article or multiple articles from a wiki or from the same company

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| wiki_article_id | int | no | if entered always returns just the entered article |
| wiki_id | int | no | if entered only gets articles from this wiki |
| search_query | string | no | search  |
| search_filter  | array | no | filter what to search for |
| amount | int | no | how many you can get back. defualt 10 |
| offset | int | no | At what index the returned results start |
| order_direction | string enum ["DESC", "ASC"] | no | which order the list is returned. defualt DESC which is newest -> oldest |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Fetched wiki articles",
    "data": {
        "articles": [
            {
                "wiki_article_id": 3,
                "title": "Test Wiki",
                "content": "Example content for wiki",
                "user_id": 6,
                "creation_date": "2025-12-09 21:31:58",
                "general": "[\"Some general info for wiki\"]",
                "restored_from_backup_id": null,
                "wiki_id": 3,
                "wiki_owner": 6,
                "customer_id": 10
            },
            {
                "wiki_article_id": 2,
                "title": "Test Wiki",
                "content": "Example content for wiki",
                "user_id": 4,
                "creation_date": "2025-12-09 21:31:56",
                "general": "[\"Some general info for wiki\"]",
                "restored_from_backup_id": null,
                "wiki_id": 2,
                "wiki_owner": 4,
                "customer_id": 10
            },
            {
                "wiki_article_id": 1,
                "title": "Test Wiki",
                "content": "Example content for wiki",
                "user_id": 2,
                "creation_date": "2025-12-09 21:29:23",
                "general": "[\"Some general info for wiki\"]",
                "restored_from_backup_id": null,
                "wiki_id": 1,
                "wiki_owner": 2,
                "customer_id": 10
            }
        ],
        "total_count": 24,
        "offset": 0,
        "amount": 3
    }
}
```
---

# Edit Wiki Article

**Endpoint:** `/api/wiki-api/edit-wiki.php`  
**Method:** `POST`

## Description
Edit Article
only changes the provided params

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| wiki_article_id | int | yes | The article to updates ID |
| title | string | no | updated title |
| content | string | no | updated content |
| general | array | no | ex updated metadata |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Wiki article edited successfully.",
    "data": {}
}
```
---

# Get Wiki Article History / All versions

**Endpoint:** `/api/wiki-api/get-all-version.php`  
**Method:** `GET`

## Description
GETS all the the versions of a Article

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| wiki_article_id | int | yes | id of the article |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Fetched wiki article versions",
    "data": {
        "active_version": {
            "wiki_article_id": 6,
            "title": "Updated Title",
            "content": "Updated content",
            "user_id": 12,
            "creation_date": "2025-12-09 22:27:38",
            "general": "[\"Updated general info\"]",
            "restored_from_backup_id": null
        },
        "old_versions": [
            {
                "old_wiki_change_id": 8,
                "wiki_article_id": 6,
                "title": "Updated Title",
                "content": "Updated content",
                "user_id": 12,
                "creation_date": "2025-12-09 22:27:29",
                "general": "[\"Updated general info\"]",
                "restored_from_backup_id": null
            },
            {
                "old_wiki_change_id": 7,
                "wiki_article_id": 6,
                "title": "Test Wiki",
                "content": "Example content for wiki",
                "user_id": 12,
                "creation_date": "2025-12-09 21:32:06",
                "general": "[\"Some general info for wiki\"]",
                "restored_from_backup_id": null
            },
            {
                "old_wiki_change_id": 6,
                "wiki_article_id": 6,
                "title": "Updated Title",
                "content": "Updated content",
                "user_id": 12,
                "creation_date": "2025-12-09 21:32:06",
                "general": "[\"Updated general info\"]",
                "restored_from_backup_id": null
            }
        ]
    }
}
```
---

# Restore Wiki Article

**Endpoint:** `/api/wiki-api/restore-wiki-changes.php`  
**Method:** `POST`

## Description
sets the active version of a wiki to a previous one

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| old_wiki_change_id | int | yes | id of the old_wiki_change you want to restore to |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Wiki article restored successfully",
    "data": {
        "restored_backup_id": 6,
        "new_active_id": 15
    }
}
```
---

# Delete Wiki Article

**Endpoint:** `/api/wiki-api/delete-wiki-article.php`  
**Method:** `POST`

## Description
Deletes a article fully including the history / versions

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| wiki_article_id | int | yes | id of the article to delete |

## Example JSON Return

```json
{
}
```
---

# Delete Wiki

**Endpoint:** `/api/wiki-api/delete-wiki.php`  
**Method:** `POST`

## Description
Deletes a full wiki including all articles in the wiki

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| wiki_id | int | yes | id of the wiki to delete |

## Example JSON Return

```json
{

}
```

