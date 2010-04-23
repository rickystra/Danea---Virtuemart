<?php
###############################################
###              MODE FULL:                 ###
### Inserimento ex-novo di tutti i prodotti ###
### presenti in Danea, le tabelle del db    ###
### sono svuotate                           ###    
###############################################

### Costanti modificabili
define('FILE_IMAGE_R_URL', 'http://192.168.1.253:8080/virtuemart');
define('JOS_VM_CATEGORY_CATEGORY_FLYPAGE', ''); // flypage per le categorie
define('JOS_VM_CATEGORY_PRODUCTS_PER_ROW', 5); // numero di righe per le categorie
define('JOS_VM_CATEGORY_CATEGORY_BROWSEPAGE',''); // browsepage per le categorie

$mysqli = new mysqli('localhost', 'root', 'antico', 'virtuemart'); //host, username, psw, db

### Fine variabili - non modificare da qui in poi ###
## TABELLE DA SVUOTARE ##
$trunc_table = array(
		'jos_vm_manufacturer',
		'jos_vm_category',
		'jos_vm_category_xref',
		'jos_vm_product',
		'jos_vm_product_category_xref',
		'jos_vm_product_mf_xref',
		'jos_vm_product_price',
		'jos_vm_product_discount'
	);
foreach($trunc_table as $val)
{
	$mysqli->query("TRUNCATE TABLE ".$val."");
}
# Crea l'oggetto Simplexml per leggere il file articoli.xml
if (file_exists('articoli.xml'))
{
	$xml = simplexml_load_file('articoli.xml');
	$i = $x = 1;
	foreach($xml->Products->Product as $product)
	{
		
		# produttori presenti in danea
		$produttori[] = (string)$product->ProducerName;
		
		# categorie presenti in danea
		$category[] = (string)$product->Category;
		# prodotti presenti in danea
		if(!empty($product->AvailableQty))
		{
			//$saldi = (1 - ((string)$product->GrossPrice2 / (string)$product->GrossPrice1))*100;
			//$saldi = (string)$product->GrossPrice1 - (string)$product->GrossPrice3;
			$prodotti[$i] = array(
				"Codice" => (string)$product->Code,
				"Nome" => (string)$product->Description,
				"Desc" => (string)$product->Notes,
				"Categoria" => (string)$product->Category,
				"SottoCategoria" => (string)$product->Subcategory,
				"NetPrice" => (string)$product->NetPrice1,
				"GrossPrice" => (string)$product->GrossPrice1,
				"Produttore" => (string)$product->ProducerName,
				"Quantita" => (string)$product->AvailableQty,
				"Sesso" => (string)$product->CustomField1,
				"Stagione" => (string)$product->CustomField3,
				//"Saldi" => number_format($saldi, 2, '.',''),
				"Immagine" => (string)$product->Code.".jpg");
			$x++;
		}
		$i = $x;
	}
	
	# Array con indice uguale al campo product_id della tabella jos_vm_product e valore uguale al campo product_sku della stessa tabella
	$i = 1;
	foreach($prodotti as $key => $val)
	{
		$prodotti_xref[$i] = $val['Codice'];
		$i++;
	}

	# produttori presenti in danea elencati una sola volta
	$produttori = array_unique($produttori);
	
	# inserimento dei produttori di Danea nella tabella jos_vm_manufacturer
	$stmt = $mysqli->prepare("INSERT INTO jos_vm_manufacturer(mf_name, mf_category_id) VALUES(?,?)");
	$stmt->bind_param("si", $name, $id);
	foreach($produttori as $val)
	{
		$name = $val;
		$id = 1; # id delle categorie dei produttori, la tabella jos_vm_manufacturer_category contiene solo 1 categoria
		$stmt->execute();
	}
	$stmt->close();
	
	# categorie presenti in danea elencate una sola volta
	$category = array_unique($category);
	
	# cerca le sottocategorie
	foreach($category as $cat)
	{
		if($cat != '')
		{
			foreach($xml->xpath("Products/Product[Category = '".$cat."']/Subcategory") as $subcat)
			{
				# tutte le sottocategorie
				$subcategory[$cat][] = (string)$subcat;
			}
		}
	}
	foreach($category as $cat)
	{
		if($cat != '')
		{
			# array finale delle categorie e sottocategorie elencate una sola volta
			$cat_subcat[$cat] = array_unique($subcategory[$cat]);
		}
	}
	
	# prepara l'insermento delle categorie e sottocategorie nella tabella jos_vm_category
	$stmt = $mysqli->prepare("INSERT INTO jos_vm_category (vendor_id, category_name, category_publish, cdate, mdate, category_browsepage, products_per_row, category_flypage) VALUES(?,?,?,?,?,?,?,?)");
	$stmt->bind_param('issiisis', $vendor, $name, $publish, $cdate, $mdate, $category_browsepage, $products_per_row, $category_flypage);
	foreach($cat_subcat as $key => $val)
	{
		$vendor = 1;
		$name = $key;
		$publish = "Y";
		$cdate = time();
		$mdate = time();
		$category_browsepage = JOS_VM_CATEGORY_CATEGORY_BROWSEPAGE;
		$products_per_row = JOS_VM_CATEGORY_PRODUCTS_PER_ROW;
		$category_flypage = JOS_VM_CATEGORY_CATEGORY_FLYPAGE;
		$stmt->execute();
		foreach($val as $k => $v)
		{
			$vendor = 1;
			$name = $v;
			$publish = "Y";
			$cdate = time();
			$mdate = time();
			$category_browsepage = JOS_VM_CATEGORY_CATEGORY_BROWSEPAGE;
			$products_per_row = JOS_VM_CATEGORY_PRODUCTS_PER_ROW;
			$category_flypage = JOS_VM_CATEGORY_CATEGORY_FLYPAGE;
			$stmt->execute();
		}
	}
	$stmt->close();
	
	# prepara le relazioni ad albero delle sottocategorie rispetto alle categorie nella tabella category_xref
	$stmt = $mysqli->prepare("INSERT INTO jos_vm_category_xref (category_parent_id, category_child_id) VALUES (?,?)");
	$stmt->bind_param('ii', $parent, $child);
	$i = 1;
	foreach($cat_subcat as $key => $val)
	{
		$child = $i;
		$parent = 0;
		$stmt->execute();
		for($x = 1; $x < count($val) +1 ; $x++)
		{
			$child = $i + $x;
			$parent = $i;
			$stmt->execute();
		}
		$i = $i + count($val) + 1;
	}
	$stmt->close();
	
	# Crea l'array dei saldi contenuti in danea
	/*foreach($prodotti as $key => $val)
	{
		$saldi_array[] = $val['Saldi'];
	}
	$saldi_array = array_unique($saldi_array);
	sort($saldi_array);
	foreach($saldi_array as $k=>$v)
	{
		$new_saldi_array[$k+1] = $v;
	}
	$stmt = $mysqli->prepare("INSERT INTO jos_vm_product_discount VALUES (?,?,?,?,?)");
	$stmt->bind_param('iiiii', $discount_id, $amount, $is_percent, $start_date, $end_date);
	foreach($new_saldi_array as $key => $val)
	{
		$discount_id = $key;
		$amount = $val;
		$is_percent = 0;
		$start_date = 0;
		$end_date = 0;
		$stmt->execute();
	}
	$stmt->close();*/
	
	# prepara l'inserimento dei prodotti nella tabella jos_vm_product
	$stmt = $mysqli->prepare("INSERT INTO jos_vm_product (vendor_id, product_parent_id, product_sku, product_s_desc, product_desc, product_thumb_image, product_full_image, product_publish, product_in_stock, product_available_date, product_availability, product_special, product_discount_id, cdate, mdate, product_name, product_tax_id, child_options, quantity_options, product_order_levels) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
	 
	$stmt->bind_param('iissssssiissiiisisss', $vendor_id, $product_parent_id, $product_sku, $product_s_desc, $product_desc, $thumb_image, $full_image, $product_publish, $product_in_stock, $product_available_date, $product_availability, $product_special, $product_discount_id, $cdate, $mdate, $product_name, $product_tax_id, $child_options, $quantity_options, $product_order_levels);
	foreach($prodotti as $key => $val)
	{
			$vendor_id = 1;
			$product_parent_id = 0;
			$product_sku = $val['Codice'];
			$product_s_desc = $val['Desc'];
			$product_desc = $val['Desc'];
			$path='';
			if(!empty($val['Immagine']))
			{
				$path = 'resized/';
			}
			$thumb_image = $path.$val['Immagine'];
			$full_image = $val['Immagine'];
			$product_publish = 'Y';
			$product_in_stock = $val['Quantita'];
			$product_available_date = time();
			$product_availability = '3 giorni lavorativi';
			$product_special = 'N';
			/*foreach($new_saldi_array as $k => $v)
			{
				if($v == $val['Saldi'] )
				{
					if($v != "0.00")
					{
						$discount = $k;
					}else{
						$discount = '';
					}
				}
			}
			$product_discount_id = $discount;*/
			$cdate = time();
			$mdate = time();
			$product_name = $val['Nome'];
			$product_tax_id = 3;
			$child_options = 'N,N,N,N,N,N,20%,10%,';
			$quantity_options = 'hide,0,0,1';
			$product_order_levels = '0,0';
			$stmt->execute();
	}
	$stmt->close();
	
	
	
	# Crea l'array cat_subcat_xref che corrisponde alla tabella jos_vm_product_category_xref
	$i = 1;
	foreach($cat_subcat as $key => $val)
	{
		$cat_subcat_xref[$i] = $key;
		$i++;
		foreach($val as $k => $v)
		{
			$cat_subcat_xref[$i] = $v;
			$i++;
		}
	}
	# Inserisce nel db le categorie dei prodotti padre
	$stmt = $mysqli->prepare("INSERT INTO jos_vm_product_category_xref VALUES (?,?,?)"); 
	$stmt->bind_param('iii', $category_id, $product_id, $product_list);
	foreach($prodotti as $key => $val)
	{
		foreach($cat_subcat_xref as $k => $v)
		{
			foreach($prodotti_xref as $kp => $vp)
			{
				if($val['Categoria'] == $v && $val['Codice'] == $vp)
				{
					$category_id = $k;
					$product_id = $kp;
					$product_list = 1;
					$stmt->execute();
				}
				if($val['SottoCategoria'] == $v && $val['Codice'] == $vp)
				{
					$category_id = $k;
					$product_id = $kp;
					$product_list = 1;
					$stmt->execute();
				}
			}
		}
	}
	$stmt->close();
	
	# prepara l'array produttori_xref che corrisponde alla tabella jos_vm_manufacturer 
	$i = 1;
	foreach($produttori as $val)
	{
		$produttori_xref[$i] = $val;
		$i++;
	}
	# Inserisce nel db le corrispondenze dei prodotti padre e figli con i produttori
	$stmt = $mysqli->prepare("INSERT INTO jos_vm_product_mf_xref VALUES (?,?);");
	$stmt->bind_param('si', $product_id, $manufacturer_id);
	foreach($prodotti as $key => $val)
	{
		foreach($produttori_xref as $kp => $vp)
		{
			foreach($prodotti_xref as $kpr => $vpr)
			{
				if($val['Produttore'] == $vp && $val['Codice'] == $vpr)
				{
					$product_id = $kpr;
					$manufacturer_id = $kp;
					$stmt->execute();
				}
			}
		}
	}
	$stmt->close();
	
	# Tabella jos_vm_product_price
	$stmt = $mysqli->prepare("INSERT INTO jos_vm_product_price (product_id, product_price, product_currency, cdate, mdate, shopper_group_id) VALUES (?,?,?,?,?,?)");
	$stmt->bind_param('issiii', $product_id, $product_price, $product_currency, $cdate, $mdate, $shopper_group_id);
	foreach($prodotti as $key => $val)
	{
		foreach($prodotti_xref as $k => $v)
		{
			if($val['Codice'] == $v)
			{
				$product_id = $k;
				$product_price = $val['NetPrice'];
				$product_currency = 'EUR';
				$cdate = time();
				$mdate = time();
				$shopper_group_id = 5;
				$stmt->execute();
			}
		}
	}
	$stmt->close();
}
else
{
    exit('Failed to open Prodotti.xml.');
}

?>