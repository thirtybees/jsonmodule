<script type="application/ld+json">
{
  "@context": "http://schema.org/",
  "@type": "Product",
  "name": "{$product->name}",
  "image": "{$image}",
  "description": "{$product->description_short|strip_tags}",
  "mpn": "{$product->supplier_reference}",
  "upc":  "{$product->upc}",
  "ean":  "{$product->ean13}",
  "brand": {
    "@type": "Thing",
    "name": "{$product->manufacturer_name}"
  },
  {if $nbReviews}
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "{$avg_decimal}",
    "reviewCount": "{$nbReviews}"
  },
  {/if}
  "offers": {
    "@type": "Offer",
    "priceCurrency": "USD",
    "price": "{$product->price}",
    {if $product->specificPrice && $product->specificPrice.to}"priceValidUntil": "{dateFormat date=$product->specificPrice.to}",{/if}
    "itemCondition": "http://schema.org/NewCondition",
    "availability": "http://schema.org/{if $product->quantity}InStock{else}OutOfStock{/if}",
    "seller": {
      "@type": "Organization",
      "name": "RC Planet"
    }
  }
}
</script>
