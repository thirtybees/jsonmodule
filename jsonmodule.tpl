<script type="application/ld+json">{$ORGANIZATION_JSON}</script>

{if isset($path)}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
	"itemListElement": [
  {foreach from=$path item=path_item name="crumbsloop"}

  {
    "@type": "ListItem",
    "position": {$smarty.foreach.crumbsloop.iteration},
    "item": {
      "@id": "{$path_item.url}",
      "name": "{$path_item.name}"
    }
  }
  {if $smarty.foreach.crumbsloop.last}{else},{/if}

  {/foreach}
  ]
}
</script>

{/if}


 {if isset($isproduct)}
<script type="application/ld+json">{$PRODUCT_JSON}</script>

 {/if}