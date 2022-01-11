# Contao Advanced Form

The contao advanced form bundle adds a new form-field (Formpage) to forms that can be used for multipage-forms with conditions

---

> Working with **Contao 4.9** and up to **Contao 4.12** (PHP ^7.4 and PHP 8)

---

+ [Features](#features)
+ [How to install](#how-to-install-the-package)
+ [How does contao advanced form work?](#how-does-contao-advanced-form-work)
+ [Initial setup](#initial-setup)
+ [Advanced setup for conditions](#advanced-setup-for-conditions)
+ [Important Information](#important-information)
+ [Options](#options)
+ [Support](#support)
+ [License](#license)
+ [Sponsoring](#sponsoring)

## Features

- Configurable formpage / pageswitch form-field with conditions for submitted values
- A Guest mode to show specific form pages for guests only
- Does not display form-pages when conditions are not met
- Sends all collected data on specific form page
- Compatible with all form-fields from contao

## How to install the package

Install the package by using following command:

```
composer require oveleon/contao-advanced-form
```

After installing the contao-advanced-form-bundle, you should run a contao install to add the new fields.

## How does contao advanced form work

Once the installation is complete, you will be able to use a new field type called "Formpage" within Contao Forms that does act as a divider for created form fields (i.e.: radio button menu, textarea, etc.).

The 'formpage' form-field acts as a page-switch and you will be redirected to it if the condition from previous submitted values is met.


## Initial setup

1. Set up your form as usual and create your form-fields


2. Create 'formpage' fields between the form-fields that should be divided into pages

    ![Admin View: Advanced form overview](https://www.oveleon.de/share/github-assets/contao-advanced-form/advanced-form-overview.jpg)


3. Add a 'submit button label' into your form-page


4. In case you want a button to get to your previous page, add a 'back button label' as well


## Advanced setup for conditions

1. Follow the initial setup mentioned above


2. Create values that can be submitted (e.g. Radio button menu) above the form-page that should meet the condition

   ![Admin View: Advanced form overview](https://www.oveleon.de/share/github-assets/contao-advanced-form/advanced-form-radio-value.jpg)


3. Activate the "Add condition" checkbox and write your condition into it
   
   ![Admin View: Advanced form overview](https://www.oveleon.de/share/github-assets/contao-advanced-form/advanced-form-page-switch.jpg)


## Important Information
### Creating Form Pages

- The first form-page will always be divided by the first form-field and the first 'Formpage'-field.

- Following form-pages are created by wrapping them with 'Formpage'-fields.

- The last field in your form needs to be a 'Formpage'-field, otherwise it will show all form-fields (This is great to debug through your form).

### Conditions

> Conditions within page-switches (Formpages) will always work for the 
>
> **FOLLOWING** 
> 
> form-fields up to the next page-switch (Formpage)

### Syntax
```
 ${Field name of radio button menu} == '{Value of radio button menu}'
```
A radio button menu with a field name of **'Example1'**, and a submitted value of **'Option1'** will jump to this page-switch (form-page).
```
 $Example1 == 'Option1'
```

You are able to set-up complex conditions to show a certain form-page.

### Buttons

> The submit-button and back-button are set up for the 
> 
> **PREVIOUS** 
> 
> form-page. They will work for the form-fields above the page-switch (Formpage).

### Classes

> Classes will always be set for the 
>
> **PREVIOUS**
>
> form-page. They will work for the form-fields above the page-switch (Formpage).

### Protecting and hiding form-pages

> Using the option *'protect form page'* and *'show to guests only'*, will always work for the 
>
> **PREVIOUS**
>
> form-page. They will work for the form-fields above the page-switch (Formpage).
>

## Options

| Option | Description |
| --- |  --- |
| **Submit button label** |  This field will add a submit button to your form-page that can be named (Next page) |
| **Back button label** | This field will add a back button to your form-page that can be named (Previous page) |
| **Add condition** | This checkbox will activate conditions. Your conditions can be written into the text-field |
| **Protect form page** | Restricts the form page to certain member groups |
| **Show to guests only** | Hides the form page if a member is logged in |


## Support
> We **only provide support** for **bugs, and feature requests**; please only post issues about these two topics.
>
> If you need help implementing Contao Advanced Form or you are just starting out with Contao, please contact us on our [website](https://www.oveleon.de/kontakt.html#kontaktformular),
> visit the [Contao Community](https://community.contao.org/) or the [Contao Slack](https://join.slack.com/t/contao/shared_invite/enQtNjUzMjY4MDU0ODM3LWVjYWMzODVkZjM5NjdlNDRiZjk2OTI3OWVkMmQ1YjA0MTQ3YTljMjFjODkwYTllN2NkMDcxMThiNzMzZjZlOGU),
> you will be able to find more help there.
>
> This will help us to keep the issues related to this plugin and solve them faster.


## License

This project is licensed under the AGPL-3.0 License — check  <a href="/oveleon/contao-advanced-form/blob/master/LICENSE">LICENSE</a> for more details.

## Sponsoring

If you find this plugin useful, please consider [sponsoring us](https://github.com/sponsors/oveleon) to help contribute to our time invested and to further development of this and other open source projects. Thank you for your support! - [Oveleon](https://www.oveleon.de).