# Export Drupal Content

To use the Drupal 7 content on this Gatsby site you have to export it first as Markdown.

There are two scripts, one for exporting nodes and the other for exporting users.

node-migrate.php
user-migrate.php

The files have to be placed on the root of the docroot folder of the original Drupal 7 website and execute them from a fully functional environment.

To export users
```
php user-migrate.php
```

To export nodes you have to specify the node type.
```
php node-migrate.php [node type]
```

For example for blog posts.
```
php node-migrate.php blog
```

Users and content will be exported to the static folder on the root of the project.
