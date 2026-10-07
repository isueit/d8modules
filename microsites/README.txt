Some prep work needs to be done before you can enable this module

1) Modify composer.json file, add these lines to the patches section:
            "drupal/group": {
                "Get a token of a node's parent group to create a pathauto pattern": "https://www.drupal.org/files/issues/2025-05-05/2774827-127.patch",
                "Delete nodes when the group is deleted": "web/modules/custom/d8modules/microsites/patches/group-deletenodes.patch"
            },

2) Run the following commands
   composer require 'drupal/group:^3.3' --no-install
   composer require 'drupal/group_content_menu:^3.0' --no-install
   composer require 'drupal/groupmedia:^4.0' --no-install
   composer update --with-dependencies

   drush en microsites
   drush en microsites_media_filter

   Go to /admin/config/content/exclude-node-title and on Microsite Landing Page, allow it to exclude the title

This should enable the microsites module, which creates a group type, a group content menu type, and a content type.

Some of the things that happen when microsites and microsites_media_filter are installed:
  - Creates a group type called microsites
  - Creates a microsite_landing_page content type
  - Creates relationships between microsites and media/content types
  - Sets some permissions
  - When editing the group content, only media with a relationship is shown


Next, you will need to create your first group. When you create a group:
  - The group is created
  - A homepage for the group is created
  - A menu for the group is created
  - You may want to assign users to the group, most will be Group editors

Potential issues
  - The hamepage has to stay titled "Homepage"
  - Group editor can delete the home page, probably shouldn't be able to do this
  - Not sure how it will work when additional content types are created for the group
  - Not sure how staff profiles will work, and how to create a view to show all the staff profiles related to a group
  - Group membership is manual, not from the staff directory
