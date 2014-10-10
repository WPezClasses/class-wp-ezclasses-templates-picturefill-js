# Org: WPezClasses
### Product: Class_WP_ezClasses_Templates_Picturefill_js

##### An ez-tized fork of ChrisB's WordPress plugin RespImage that makes Scott Jehl's picturefill.js WP-friendly. 

- http://elf02.de/2014/07/14/respimage-wordpress-plugin/

- http://scottjehl.github.io/picturefill/

===============================================================================================

#### "Demo"

This demo / example plugin shows you how you can use this class:

https://github.com/WPezPlugins/wp-ezplugins-templates-picturefill-js


===============================================================================================

#### How is Class_WP_ezClasses_Templates_Picturefill_js different (than ChrisB's approach)?

- There's no add_image_size(). You can (and probably should) take care of that on your own elsewhere.

- You can use it for images in the WP Media Library but *not* in the_content(). (See plugin example above.)

- No WP options. All settings are done via code. Set it up once and move on. The chance of human error is mitigated.