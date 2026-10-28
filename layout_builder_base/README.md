# Layout Builder Base

The layout_builder_base module provides you a plugin base to create complex
Layouts. It can be used by developers as a base to save time and focus on your
styling. This module also provides a submodule wich provides you different
layouts that can be used to build pages.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/layout_builder_base).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/layout_builder_base).


## Table of contents

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

The requirement is that you already have installed the module layout_builder
and layout_discovery from Drupal core.


## Installation

This is like any other module.

1. Add it to your project with composer
   `composer require drupal/layout_builder_base`.

1. Enable the module layout_builder_base and/or layout_builder_base_library if
   you want to use the predefined layouts.

1. Visit `/admin/config/content/layout_builder_base` and configure the different
   options if you want to change the default values or override the choices.


## Configuration

### For site builders

   - Use the predefined layouts that the module provides. In order to use them,
     you have to enable layout_builder_base_library.

### For developers

   - Before using this method, be sure that the part for site builders is not enough
     for you. A system to alter the choices with an interface has been added, so you
     can add options and change the default value by only managing your
     configuration. If you need new API functions or more complex things, you are
     probably at the right place.

If you want to create your own layouts, you have two steps:

- Write your CSS. In order to work correctly, you have to create one CSS class
  for each aspect of your layout. For example, you want to propose a red
  background. The following CSS would be perfect:

.layout--background--red {
  background-color: red;
}

You have two ways of customizing the layouts, using the built ones from the
module or create your own by rewriting all the API provided by the module.

- Recommended method

We recommend to use the layouts provided by the layout_builder_base_library
to build your pages. In order to customize the options that appears in the
different properties of the layout, just use the hooks that allows you to
override or add new options. The list of the hooks is available in the
layout_builder_base.api.php file. The existing layouts already make the call
to the hooks. If you override the methods, be sure to still call it otherwise
the interface for managing the options will be broken.

- Custom method: Rewriting the API

Take that method only if you see that the recommended method is not enough and
you need more freedom to customize the layouts.

Create your own Layout class and extends DefaultLayoutBase provided by the
module. If we take our example of background red again, you will have to
override the method we provide for the background getBackgroundOptions. When
you are overriding a method to provide options, just use the CSS class as the
key of the array and the value will be the label that the user will pick in the
select.

If you take that path and you want to reuse part of the features from the
layout_builder_base_library layouts, extends directly the classes from the
module like BaseOneColumnLayout instead of DefaultLayoutBase.

```
<?php

namespace Drupal\your_module\Plugin\Layout;

use Drupal\layout_builder_base\Plugin\Layout\DefaultLayoutBase;

/**
 * Configurable layout plugin class.
 *
 * @internal
 *   Plugin classes are internal.
 */
class BaseOneColumnLayout extends DefaultLayoutBase {

  /**
   * {@inheritdoc}
   */
  protected function getBackgroundOptions() {
    return [
      'layout--background--none' => 'None',
      'layout--background--red' => 'Red',
    ];
  }

}
```

You can also extends BaseLayoutBase directly which is the class containing all
the features. You will just have to override all the abstract methods of the
class. DefaultLayoutBase is just overriding them all with no choice.
Feel free to check our Layouts to give you an idea how to write your custom
ones.

Once you created your custom class, you just have to declare your layout to
Drupal. You can follow the official documentation but basically you will have
to declare it in your layouts.yml.

your_module.layouts.yml

```
your_layout_name:
  label: 'Your Layout Label'
  path: layouts/layout-your-name
  template: your-name
  library: your_module/your_library
  class: '\Drupal\your_module\Plugin\Layout\YourCustomLayoutClass'
  category: 'Columns: 1'
  default_region: content
  icon_map:
    - [content]
  regions:
    content:
      label: Content
```

Adapt the following definition to your needs, there is only one region for the
layout above. Don't forget to create the template of your layout
your_module/layouts/layout-your-name/your-name.html.twig !
Once you've done all those steps, your layout should appear in the interface
and you can start using it to build your pages.


## Maintainers

- Philippe Joulot - [phjou](https://www.drupal.org/u/phjou)
