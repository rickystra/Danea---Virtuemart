<?php
###############################################
###              MODE FULL:                 ###
### Inserimento ex-novo di tutti i prodotti ###
### presenti in Danea, le tabelle del db    ###
### sono svuotate                           ###    
###############################################

### Costanti modificabili
define('FILE_IMAGE_R_URL', 'http://192.168.1.253:8080/joomla');
define('JOS_VM_CATEGORY_CATEGORY_FLYPAGE', 'str_flypage.tpl'); // flypage per le categorie
define('JOS_VM_CATEGORY_PRODUCTS_PER_ROW', 5); // numero di righe per le categorie
define('JOS_VM_CATEGORY_CATEGORY_BROWSEPAGE','str_browse_1'); // browsepage per le categorie

### Fine variabili - non modificare da qui in poi ###

$mysqli = new mysqli('localhost', 'root', 'antico', 'joomla');

$create = "CREATE TABLE IF NOT EXISTS joomla.jos_vm_product_type_1 (
  `product_id` int(11) NOT NULL,
  `Sesso` text,
  `Stagione` varchar(255) default NULL,
  `Categoria` varchar(255) default NULL,
  `SottoCategoria` varchar(255) default NULL,
  `Designer` text,
  PRIMARY KEY  (`product_id`),
  KEY `idx_product_type_1_Stagione` (`Stagione`),
  KEY `idx_product_type_2_Categoria` (`Categoria`),
  KEY `idx_product_type_2_SottoCategoria` (`SottoCategoria`),
  FULLTEXT KEY `idx_product_type_2_Designer` (`Designer`),
  FULLTEXT KEY `idx_product_type_1_Sesso` (`Sesso`)) 
  ENGINE=MyISAM DEFAULT CHARSET=utf8;";
$mysqli->query($create);

# Elimina tutti i dati nella tabella jos_vm_manufacturer
$truncate = "TRUNCATE TABLE jos_vm_manufacturer";
$mysqli->query($truncate);

# Elimina tutti i dati nella tabella jos_vm_category
$truncate = "TRUNCATE TABLE jos_vm_category";
$mysqli->query($truncate);

# Elimina tutti i dati nella tabella jos_vm_manufacturer_xref
$truncate = "TRUNCATE TABLE jos_vm_category_xref";
$mysqli->query($truncate);

# Elimina tutti i dati nella tabella jos_vm_product
$truncate = "TRUNCATE TABLE jos_vm_product";
$mysqli->query($truncate);

# Elimina tutti i dati nella tabella jos_vm_product_attribute
$truncate = "TRUNCATE TABLE jos_vm_product_attribute";
$mysqli->query($truncate);

# Elimina tutti i dati nella tabella jos_vm_product_attribute_sku
$truncate = "TRUNCATE TABLE jos_vm_product_attribute_sku";
$mysqli->query($truncate);

# Elimina tutti i dati nella tabella jos_vm_product_category_xref
$truncate = "TRUNCATE TABLE jos_vm_product_category_xref";
$mysqli->query($truncate);

# Elimina tutti i dati nella tabella jos_vm_product_mf_xref
$truncate = "TRUNCATE TABLE jos_vm_product_mf_xref";
$mysqli->query($truncate);

# Elimina tutti i dati nella tabella jos_vm_product_price
$truncate = "TRUNCATE TABLE jos_vm_product_price";
$mysqli->query($truncate);

# Elimina tutti i dati nella tabella jos_vm_product_product_type_xref
$truncate = "TRUNCATE TABLE jos_vm_product_product_type_xref";
$mysqli->query($truncate);

# Elimina tutti i dati nella tabella jos_vm_product_type_1
$truncate = "TRUNCATE TABLE jos_vm_product_type_1";
$mysqli->query($truncate);

# Elimina tutti i dati nella tabella jos_vm_product_files
$truncate = "TRUNCATE TABLE jos_vm_product_files";
$mysqli->query($truncate);

# Elimina tutti i dati nella tabella jos_vm_product_discount
$truncate = "TRUNCATE TABLE jos_vm_product_discount";
$mysqli->query($truncate);

# Elimina tutti i dati nella tabella jos_vm_product_type_parameter
$truncate = "TRUNCATE TABLE jos_vm_product_type_parameter";
$mysqli->query($truncate);

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
			$saldi = (1 - ((string)$product->GrossPrice2 / (string)$product->GrossPrice1))*100;
			$prodotti[$i] = array(
				"Codice" => (string)$product->Code,
				"Nome" => (string)$product->Description,
				"Desc" => "<b>".(string)$product->ProducerName."</b><br/>".(string)$product->Subcategory,
				"Html" => (string)$product->DescriptionHtml,
				"Categoria" => (string)$product->Category,
				"SottoCategoria" => (string)$product->Subcategory,
				"NetPrice" => (string)$product->NetPrice1,
				"GrossPrice" => (string)$product->GrossPrice1,
				"Produttore" => (string)$product->ProducerName,
				"Quantita" => (string)$product->AvailableQty,
				"Sesso" => (string)$product->CustomField1,
				"Stagione" => (string)$product->CustomField3,
				"Saldi" => number_format($saldi, 2, '.',''),
				"Immagine" => (string)$product->Code.".jpg");
			foreach($xml->xpath("Products/Product[InternalID = '".(string)$product->InternalID."']/Variant") as $variant)
			{
				if(!empty($variant->AvailableQty)){
				$col_img = str_replace(" ","_",strtolower((string)$variant->Color));
				$prodotti[$i]['varianti'][] = array(
					"Codice" => (string)$product->Code."/".(string)$variant->Size."/".(string)$variant->Color,
					"Nome" => (string)$product->Description,
					"Categoria" => (string)$product->Category,
					"SottoCategoria" => (string)$product->Subcategory,
					"NetPrice" => (string)$product->NetPrice1,
					"GrossPrice" => (string)$product->GrossPrice1,
					"Produttore" => (string)$product->ProducerName,
					"Quantita" => (string)$variant->AvailableQty,
					"Taglia" => (string)$variant->Size,
					"Colore" => (string)$variant->Color,
					"Sesso" => (string)$product->CustomField1,
					"Immagine" => (string)$product->Code."_".$col_img."_f.jpg",
					"Immagine_R" => (string)$product->Code."_".$col_img."_r.jpg",
					"Stagione" => (string)$product->CustomField3);
				$x++;
				}
			}
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
		foreach($val['varianti'] as $k => $v)
		{
			$prodotti_xref[$i] = $v['Codice'];
			$i++;
		}
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
	foreach($prodotti as $key => $val)
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
		$is_percent = 1;
		$start_date = 0;
		$end_date = 0;
		$stmt->execute();
	}
	$stmt->close();
	
	# prepara l'inserimento dei prodotti nella tabella jos_vm_product
	$stmt = $mysqli->prepare("INSERT INTO jos_vm_product (vendor_id, product_parent_id, product_sku, product_s_desc, product_desc, product_thumb_image, product_full_image, product_publish, product_in_stock, product_available_date, product_availability, product_special, product_discount_id, cdate, mdate, product_name, product_tax_id, child_options, quantity_options, product_order_levels) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
	 
	$stmt->bind_param('iissssssiissiiisisss', $vendor_id, $product_parent_id, $product_sku, $product_s_desc, $product_desc, $thumb_image, $full_image, $product_publish, $product_in_stock, $product_available_date, $product_availability, $product_special, $product_discount_id, $cdate, $mdate, $product_name, $product_tax_id, $child_options, $quantity_options, $product_order_levels);
	foreach($prodotti as $key => $val)
	{
			$vendor_id = 1;
			$product_parent_id = 0;
			$product_sku = $val['Codice'];
			$product_s_desc = $val['Desc'];
			$product_desc = $val['Html'];
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
			foreach($new_saldi_array as $k => $v)
			{
				if($v == $val['Saldi'])
				{
					$discount = $k;
				}
			}
			$product_discount_id = $discount;
			$cdate = time();
			$mdate = time();
			$product_name = $val['Nome'];
			$product_tax_id = 3;
			$child_options = 'N,N,N,N,N,N,20%,10%,';
			$quantity_options = 'none,0,0,1';
			$product_order_levels = '0,0';
			$stmt->execute();
			foreach($val['varianti'] as $v)
			{
				$vendor_id = 1;
				$product_parent_id = $key;
				$product_sku = $v['Codice'];
				$path='';
				if(!empty($v['Immagine']))
				{
					$path = 'resized/';
				}
				$thumb_image = $path.$v['Immagine'];
				$full_image = $v['Immagine'];
				$product_publish = 'Y';
				$product_in_stock = $v['Quantita'];
				$product_available_date = time();
				$product_availability = '3 giorni lavorativi';
				$product_special = 'N';
				$product_discount_id = $discount;
				$cdate = time();
				$mdate = time();
				$product_name = $v['Nome'];
				$product_tax_id = 3;
				$child_options = 'N,N,N,N,N,N,20%,10%,';
				$quantity_options = 'none,0,0,1';
				$product_order_levels = '0,0';
				$stmt->execute();
			}
		
	}
	$stmt->close();
	
	# prepara l'inserimento degli attributi nella tabella jos_vm_product_attribute 
	
	$stmt = $mysqli->prepare("INSERT INTO jos_vm_product_attribute (product_id, attribute_name, attribute_value) VALUES(?,?,?)");
	$stmt->bind_param('sss',$product_id, $attribute_name, $attribute_value);
	foreach($prodotti as $key => $val)
	{
		foreach($val['varianti'] as $v)
		{
			foreach($prodotti_xref as $kp => $vp)
			{
				if($v['Codice'] == $vp)
				{
					$product_id = $kp;
					$attribute_name = 'Taglia';
					$attribute_value = $v['Taglia'];
					$stmt->execute();
					$product_id = $kp;
					$attribute_name = 'Colore';
					$attribute_value = $v['Colore'];
					$stmt->execute();
				}
			}
		}
	}
	$stmt->close();
	
	# prepara l'insermento degli attributi nella tabella jos_vm_product_attribute_sku
	$stmt = $mysqli->prepare("INSERT INTO jos_vm_product_attribute_sku (product_id, attribute_name, attribute_list) VALUES (?,?,?)");
	$stmt->bind_param('iss', $product_id, $attribute_name, $attribute_list);
	foreach($prodotti as $key=>$val)
	{
		foreach($prodotti_xref as $k => $v)
		{
			if($val['Codice'] == $v)
			{
				$product_id = $k;
				$attribute_name = 'Colore';
				$attribute_list = 0;
				$stmt->execute();
				$product_id = $k;
				$attribute_name = 'Taglia';
				$attribute_list = 0;
				$stmt->execute();
			}
		}
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
				foreach($val['varianti'] as $v)
				{
					if($val['Produttore'] == $vp && $v['Codice'] == $vpr)
					{
						$product_id = $kpr;
						$manufacturer_id = $kp;
						$stmt->execute();
					}
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
			foreach($val['varianti'] as $kp => $vp)
			{
				if($vp['Codice'] == $v)
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
	}
	$stmt->close();
	
	# Tabella jos_vm_product_type
	
	// code here
	
	
	# Tabella jos_vm_product_type_parameter
	# Prepara l'array
	$cat_subcat_param = implode(';', $cat_subcat_xref);
	$designer_param = implode(';', $produttori);
	$parameter = array(
		'Sesso' => 'Uomo;Donna',
		'Stagione' => "Autunno/Inverno;Primavera/Estate;Anno Intero",
		'Categoria' => $cat_subcat_param, 
		'SottoCategoria' => $cat_subcat_param, 
		'Designer' => $designer_param);
		
	$stmt = $mysqli->prepare("INSERT INTO jos_vm_product_type_parameter (product_type_id, parameter_name, parameter_label, parameter_list_order, parameter_type, parameter_values, parameter_multiselect) VALUES (?,?,?,?,?,?,?)");
	$stmt->bind_param('ississs', $product_type_id, $parameter_name, $parameter_label, $parameter_list_order, $parameter_type, $parameter_values, $parameter_multiselect);
	$i = 1;
	foreach($parameter as $key => $val)
	{
		$product_type_id = 1;
		$parameter_name = $key;
		$parameter_label = $key;
		$parameter_list_order = $i;
		$parameter_type = 'V';
		$parameter_values = $val;
		$parameter_multiselect = 'N';
		$stmt->execute();
		$i++;
	}
	$stmt->close();
	
	# Tabella jos_vm_product_type_1
	$stmt = $mysqli->prepare("INSERT INTO jos_vm_product_type_1 VALUES(?,?,?,?,?,?)");
	$stmt->bind_param('isssss', $product_id, $sesso, $stagione, $categoria, $sottocategoria, $designer);
	foreach($prodotti as $key => $val)
	{
		foreach($prodotti_xref as $k => $v)
		{
			if($val['Codice'] == $v)
			{
				$product_id = $k;
				$sesso = $val['Sesso'];
				switch($val['Stagione'])
				{
					case "A/I":
						$stagione = "Autunno/Inverno";
					break;
					case "P/E":
						$stagione = "Primavera/Estate";
					break;
					default:
						$stagione = "Anno Intero";
					break;
				}
				$categoria = $val['Categoria'];
				$sottocategoria = $val['SottoCategoria'];
				$designer = $val['Produttore'];
				$stmt->execute(); 
			}
		}
	}
	
	$stmt->close();
	
	# Tabella jos_vm_product_product_type_xref
	$stmt = $mysqli->prepare("INSERT INTO jos_vm_product_product_type_xref VALUES(?,?)");
	$stmt->bind_param('ii', $product_id, $product_type_id);
	foreach($prodotti as $key => $val)
	{
		foreach($prodotti_xref as $k => $v)
		{
			if($val['Codice'] == $v)
			{
				$product_id = $k;
				$product_type_id = 1;
				$stmt->execute(); 
			}
		}
	}
	$stmt->close();
	
	# Tabella jos_vm_product_product_type_xref
	$stmt = $mysqli->prepare("INSERT INTO jos_vm_product_product_type_xref VALUES(?,?)");
	$stmt->bind_param('ii', $product_id, $product_type_id);
	foreach($prodotti as $key => $val)
	{
		foreach($prodotti_xref as $k => $v)
		{
			if($val['Codice'] == $v)
			{
				$product_id = $k;
				$product_type_id = 2;
				$stmt->execute(); 
			}
		}
	}
	$stmt->close();
	
	# Tabella jos_vm_product_files
	$stmt = $mysqli->prepare("INSERT INTO jos_vm_product_files (file_product_id, file_name, file_title, file_extension, file_mimetype, file_url, file_published, file_is_image, file_image_height, file_image_width, file_image_thumb_height, file_image_thumb_width) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
	$stmt->bind_param('isssssiiiiii', $file_product_id, $file_name, $file_title, $file_extension, $file_mimetype, $file_url, $file_published, $file_is_image, $file_image_height, $file_image_width, $file_image_thumb_height, $file_image_thumb_width);
	foreach($prodotti as $key => $val)
	{
		foreach($val['varianti'] as $k => $p)
		{
			foreach($prodotti_xref as $kx => $vx)
			{
				if($p['Codice'] == $vx)
				{
					$file_product_id = $kx;
					$file_name = "/components/com_virtuemart/shop_image/product/".$p['Immagine_R'];
					$file_title = $p['Nome'];
					$file_extension = 'jpg';
					$file_mimetype = 'image/jpeg';
					$file_url = FILE_IMAGE_R_URL."/components/com_virtuemart/shop_image/product/".$p['Immagine_R'];
					$file_published = 1;
					$file_is_image = 1;
					$file_image_height = 2000;
					$file_image_width = 1339;
					$file_image_thumb_height = 50;
					$file_image_thumb_width = 33;
					$stmt->execute();
				}
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