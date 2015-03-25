<?php

try {

    $cmsBlock = Mage::getModel("cms/block");

    $blockId = Mage::getStoreConfig('payment/laskuyritykselle/checkout_info');
    $blockExists = $cmsBlock->load($blockId)->getContent();

    // If block doesn't exist, create it.

    if (!$blockExists) {

        // Add static block (infobox for checkout page).

        $blockContent = '<div class="laskuyritykselle_info">
  <img src="{{skin url=\'images/enterpay/laskuyritykselle/logo.png\'}}" />
  <p>{{config path=\'payment/laskuyritykselle/title\'}} on yritysten tarpeisiin kehitetty kätevä ja nopea verkkomaksamisen ratkaisu. Valitsemalla {{config path=\'payment/laskuyritykselle/title\'}} maksutavan saat yrityksellesi ostokset laskulla joko eLaskuna tai sähköpostilaskuna. Maksutapa on ostavalle yritykselle maksuton palvelu.</p>
  <p><a href="http://www.laskuyritykselle.fi/ostavalle-yritykselle" target="_blank">Lue lisää</a></p>
</div>';

        $newBlock = Mage::getModel("cms/block");
        $newBlock->setTitle("LaskuYritykselle.fi payment method info");
        $newBlock->setIdentifier("laskuyritykselle_info");
        $newBlock->setStores(0);
        $newBlock->setIsActive(1);
        $newBlock->setContent($blockContent);

        $newBlock->save();

    }

} catch (Exception $e) {
    var_dump($e);
}

