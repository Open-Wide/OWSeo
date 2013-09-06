{def $title_max = ezini('EditorInputSettings','MaxTitle','owseo.ini')
     $descr_max = ezini('EditorInputSettings','MaxDescription','owseo.ini')
     $metadata = seo_parse($attribute.object.class_identifier)
}

<fieldset>
<div class="block">
    <label>{'Title'|i18n( 'design/standard/class/datatype' )}: {if $metadata.title}<span class="classattribute-description"><b>{'Default rule'|i18n( 'design/standard/class/datatype' )}</b>: {$metadata.title|wash()}</span>{/if}</label>
    <input maxlength={$title_max} id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' 
    )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}_title" class="box ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}" type="text" name="{$attribute_base}_owseometadata_data_array_{$attribute.id}[title]" size="100" value="{$attribute.content.title|wash()}" />
</div>

<div class="block">
    <label>{'Description'|i18n( 'design/standard/class/datatype' )}: {if $metadata.description}<span class="classattribute-description"><b>{'Default rule'|i18n( 'design/standard/class/datatype' )}</b>: {$metadata.description|wash()}</span>{/if}</label>
    <input maxlength="{$descr_max}" id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' 
    )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}_description" class="box ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}" type="text" name="{$attribute_base}_owseometadata_data_array_{$attribute.id}[description]" size="100" value="{$attribute.content.description|wash()}" />
</div>

<div class="block">
    <label>{'Keywords'|i18n( 'design/standard/class/datatype' )}: {if $metadata.keywords}<span class="classattribute-description"><b>{'Default rule'|i18n( 'design/standard/class/datatype' )}</b>: {$metadata.keywords|wash()}</span>{/if}</label>
    <input id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute'
    )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}_keywords" class="box ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}" type="text" name="{$attribute_base}_owseometadata_data_array_{$attribute.id}[keywords]" size="100" value="{$attribute.content.keywords|wash()}" />
</div>
</fieldset>