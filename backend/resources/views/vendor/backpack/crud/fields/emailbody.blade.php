<x-wysiwyg id="email_message" name="content">{{ old('content', $field['value'] ?? ($field['default'] ?? '')) }}</x-wysiwyg>
