<?php 

/** 
* Generated at: 2019-02-01T09:39:35+01:00
* Inheritance: no
* Variants: no
* Changed by: admin (2)
* IP: 127.0.0.1


Fields Summary: 
- sliderImages [imageGallery]
*/ 


return Pimcore\Model\DataObject\ClassDefinition::__set_state(array(
   'id' => '8',
   'name' => 'ConfigLandingpage',
   'description' => '',
   'creationDate' => 0,
   'modificationDate' => 1549010375,
   'userOwner' => 2,
   'userModification' => 2,
   'parentClass' => '',
   'listingParentClass' => '',
   'useTraits' => '',
   'listingUseTraits' => '',
   'allowInherit' => false,
   'allowVariants' => NULL,
   'showVariants' => false,
   'layoutDefinitions' => 
  Pimcore\Model\DataObject\ClassDefinition\Layout\Panel::__set_state(array(
     'fieldtype' => 'panel',
     'labelWidth' => 100,
     'layout' => NULL,
     'name' => 'pimcore_root',
     'type' => NULL,
     'region' => NULL,
     'title' => NULL,
     'width' => NULL,
     'height' => NULL,
     'collapsible' => false,
     'collapsed' => false,
     'bodyStyle' => NULL,
     'datatype' => 'layout',
     'permissions' => NULL,
     'childs' => 
    array (
      0 => 
      Pimcore\Model\DataObject\ClassDefinition\Layout\Panel::__set_state(array(
         'fieldtype' => 'panel',
         'labelWidth' => 100,
         'layout' => NULL,
         'name' => 'Layout',
         'type' => NULL,
         'region' => NULL,
         'title' => '',
         'width' => NULL,
         'height' => NULL,
         'collapsible' => false,
         'collapsed' => false,
         'bodyStyle' => '',
         'datatype' => 'layout',
         'permissions' => NULL,
         'childs' => 
        array (
          0 => 
          Pimcore\Model\DataObject\ClassDefinition\Data\ImageGallery::__set_state(array(
             'fieldtype' => 'imageGallery',
             'queryColumnType' => 
            array (
              'images' => 'text',
              'hotspots' => 'text',
            ),
             'columnType' => 
            array (
              'images' => 'text',
              'hotspots' => 'text',
            ),
             'phpdocType' => '\\Pimcore\\Model\\DataObject\\Data\\ImageGallery',
             'width' => NULL,
             'height' => NULL,
             'uploadPath' => NULL,
             'ratioX' => NULL,
             'ratioY' => NULL,
             'predefinedDataTemplates' => NULL,
             'name' => 'sliderImages',
             'title' => 'Slider Bilder',
             'tooltip' => NULL,
             'mandatory' => NULL,
             'noteditable' => NULL,
             'index' => NULL,
             'locked' => NULL,
             'style' => NULL,
             'permissions' => NULL,
             'datatype' => 'data',
             'relationType' => false,
             'invisible' => false,
             'visibleGridView' => true,
             'visibleSearch' => true,
          )),
        ),
         'locked' => false,
      )),
    ),
     'locked' => false,
  )),
   'icon' => '',
   'previewUrl' => '',
   'group' => '',
   'showAppLoggerTab' => false,
   'linkGeneratorReference' => '',
   'propertyVisibility' => 
  array (
    'grid' => 
    array (
      'id' => true,
      'key' => false,
      'path' => true,
      'published' => true,
      'modificationDate' => true,
      'creationDate' => true,
    ),
    'search' => 
    array (
      'id' => true,
      'key' => false,
      'path' => true,
      'published' => true,
      'modificationDate' => true,
      'creationDate' => true,
    ),
  ),
   'dao' => NULL,
));
