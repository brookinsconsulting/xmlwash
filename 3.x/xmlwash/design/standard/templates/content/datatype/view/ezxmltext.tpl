{if is_set($maxlength)}
{$attribute.content.output.output_text|shorten($maxlength)|xmlwash()}
{else}
{$attribute.content.output.output_text|xmlwash()}
{/if}

