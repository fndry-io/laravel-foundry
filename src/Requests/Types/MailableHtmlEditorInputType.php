<?php

namespace Foundry\Requests\Types;

/**
 * Class EditorInputType
 *
 * @package Foundry\Requests\Types
 */
class MailableHtmlEditorInputType extends HtmlEditorInputType {

    public function __construct(
        string $name,
        string $label = null,
        bool $required = true,
        string $value = null,
        string $position = 'full',
        string $rules = null,
        string $id = null,
        string $placeholder = null
    ) {
        $type = 'mailable-html-editor';
        parent::__construct( $name, $label, $required, $value, $position, $rules, $id, $placeholder, $type );
    }
}
