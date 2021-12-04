---
description: Start a new project with Phyrus
---

# Setup guide

### With composer

To start a new Phyrus project with Composer, just run this line in your terminal:

```
composer create-project phyrus/project MyProject
```

A new folder will be created and if you run a local server there, you should see the website just by going to http://localhost.

### Without composer

If, for some reason, you prefer to create a project without composer, Phyrus allows it. Just download the code from both repositories **phyrus/project** and **phyrus/framework**. Place the framework inside the project folder, and then edit the **index.php** file to change the import of composer by the import of the framework:

```
// Remove
require(__DIR__.'/vendor/autoload.php');

// Add
require(__DIR__ . '/framework/index.php');
```

### Connecting a Database

Connecting a database **is not required**. You can have a project without a database, so you don't need to start.

In any case, if you want to configure it, the credentials for the database are in the configuration file in the root folder: **config.json**.
