Some prep work needs to be done before you can enable this module

1) Modify composer.json file, add these lines to the patches section:
            "drupal/group": {
                "Get a token of a node's parent group to create a pathauto pattern": "https://www.drupal.org/files/issues/2025-05-05/2774827-127.patch",
                "Delete nodes when the group is deleted": "web/local/patches/group-deletenodes.patch"
            },

2) Run the following commands
   composer require 'drupal/group:^3.3' --no-install
   composer require 'drupal/group_content_menu:^3.0' --no-install
   composer update --with-dependencies

   drush en microsites 

This should enable the microsites module, which creates a group type, a group content menu type, and a content type.

Next, you will need to create your first group.
