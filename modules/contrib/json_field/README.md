# JSON Field

This module provides mechanisms for storing JSON data in fields, and various
tools to make editing it easier than using a plain text field.

## Supported field types

Three field types are provided by the module, but how each database system
supports them will vary:

* "JSON (text)"
  This option uses a VARCHAR or TEXT column on all supported database systems.
* "JSON (raw)"
  MySQL: Stores as JSON.
  PostgreSQL: Stored as JSON.
  MariaDB: Stored as LONGTEXT
  Sqlite: Stored as TEXT.
* "JSONB/JSON (raw)"
  MySQL: Stores as JSON.
  PostgreSQL: Stored as JSONB.
  MariaDB: Stored as LONGTEXT
  Sqlite: Stored as TEXT.

### Notes

MariaDB uses a [LONGTEXT column to store JSON data](https://mariadb.com/kb/en/json-data-type/),
which will be confusing at first. However, it then supports queries executed
against the column.

## Installation

You need ideally the jsonview client library from
https://github.com/yesmeck/jquery-jsonview/releases. Put the latest release into
the site's "libraries" folder so the folder structure looks like the following:

```
- core
- libraries
 \- jsonview
   \- dist
     \- jquery.jsonview.css
     \- jquery.jsonview.js
- modules
```

## Using composer

If you are using composer you can add the following code to your root
 `composer.json`:

```
"repositories": [
  {
    "type": "package",
    "package": {
      "name": "josdejong/jsoneditor",
      "version": "v5.29.1",
      "type": "drupal-library",
      "dist": {
        "url": "https://github.com/josdejong/jsoneditor/archive/v5.29.1.zip",
        "type": "zip"
      },
      "source": {
        "url": "https://github.com/josdejong/jsoneditor",
        "type": "git",
        "reference": "v5.29.1"
      }
    }
  },
  {
    "type": "package",
    "package": {
      "name": "yesmeck/jquery-jsonview",
      "version": "v1.2.3",
      "type": "drupal-library",
      "dist": {
        "url": "https://github.com/yesmeck/jquery-jsonview/archive/v1.2.3.zip",
        "type": "zip"
      },
      "source": {
        "url": "https://github.com/yesmeck/jquery-jsonview",
        "type": "git",
        "reference": "v1.2.3"
      }
    }
  }
],
```

And add the libraries to your project with:

```
composer require yesmeck/jquery-jsonview
composer require josdejong/jsoneditor
```

## Issues with core

Using JSON columns will cause problems with core's database export script due
to it not directly supporting "json" field types; core issues exist to add the
necessary API changes:

* MySQL/MariaDB: https://www.drupal.org/project/drupal/issues/3143512
* PostgreSQL: https://www.drupal.org/project/drupal/issues/2472709
