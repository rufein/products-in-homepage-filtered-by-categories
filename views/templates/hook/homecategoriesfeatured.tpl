{*
* 2007-2014 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
*}
<!-- MODULE Home Categories Featured Products -->
{foreach from=$homefeaturedproducts key=k item=products}
{counter name=active_ul assign=active_ul}
{if isset($products) && $products}
  {include file="$tpl_dir./product-list.tpl" class='homefeatured tab-pane' id="{$homefeaturedcategories[$k]->link_rewrite}" active=$active_ul}
{else}
<ul id="{$homefeaturedcategories[$k]->link_rewrite}" class="homefeatured tab-pane{if isset($active_ul) && $active_ul == 1} active{/if}">
  <li class="alert alert-info">{l s='No categories featured products at this time.' mod='homecategoriesfeatured'}</li>
</ul>
{/if}
{/foreach}

	
