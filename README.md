# DataObjectFormBundle

This bundle allows editing data objects in Pimcore frontend by mapping the information stored in object class definitions
to frontend form builder.

## Configuration

Add to twig configuration if you need to use our custom form types (see features).
```
twig:
    form_themes:
        - '@DataObjectForm/form/custom_types.html.twig'
```

## Features

### New FormTypes

* ObjectRelationType (BETA)

### Supported types

* Select
* Country
* Input / Firstname / Lastname
* Textarea
* Gender
* Checkbox
* Advanced ManyToMany Object Relation (BETA)

### Supported properties

* Title
* Tooltip
* Mandatory field
* Not editable
* Invisible
* Selection Options

### NOT supported properties

* Default value (except for checkbox)
* Default value generator service / class
* Options provider