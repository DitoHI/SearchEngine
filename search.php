<?php
	// include composer autoloader
	require_once __DIR__ . '/vendor/autoload.php';
	session_start();

	// create stemmer
	// cukup dijalankan sekali saja, biasanya didaftarkan di service container
	$stemmerFactory = new \Sastrawi\Stemmer\StemmerFactory();
	$stemmer  = $stemmerFactory->createStemmer();
?>

<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" href="style.css">
	<title>Search College</title>
	<link rel="shortcut icon" href="favicon.png" >
</head>
<body>
	<?php
		if (isset($_POST['awal'])) {
			$awal = $_POST['awal'];
		}
		cekHome();
		//START TIME - Pengukuran waktu
		$awal = microtime(true);
		if (isset($_POST['submit']) || $awal == 'true' ){
			$_SESSION['keyword']="";
			//function : get  the input value from user
			function test_input($data) {
			  $data = trim($data);
			  $data = stripslashes($data);
			  $data = htmlspecialchars($data);
			  return $data;
			}
			//Counting sum of file insides directory text
			$directory = "text/";
			$filecount = 0;
			$files = glob($directory."*");
			if ($files)
				$filecount = count($files)-1;
			//make array consist of file from the text --- TOKENIZING
			for($i = 0; $i < $filecount; $i++){
				$target_files[$i] = fopen($directory.($i+1)."_teks.txt", "r");
				$words[$i] = array(" ");
				//get titles from each pages
				$title[$i] = file($directory.($i+1)."_teks.txt")[0];
				while (!feof($target_files[$i])) {
					$line = fgets($target_files[$i]);
					$word = preg_split('/\s+/', $line);
					if ($words[$i] == array(" "))	
						$words[$i] = $word;
					else if ($words[$i] != array(" "))
						$words[$i] = array_merge($words[$i], $word);
				}
				//TOKENIZING 2 --- delete empty string
				$words[$i] = array_filter($words[$i]); $words[$i] = array_values($words[$i]);
			}
			//get the stopwords and delete it --- FILTERING
			$stopwords = file($directory."stopwords.txt");
			$stopwords = preg_replace('/\s+/', '', $stopwords);
			for ($i=0;$i<$filecount;$i++){
				$sum_words = count($words[$i]);
				for ($j=0;$j<$sum_words;$j++){
					$words[$i][$j] = preg_replace("/[^A-Za-z]/", '', $words[$i][$j]);
					if (in_array(strtolower($words[$i][$j]), $stopwords))
						unset($words[$i][$j]);
				}
				$words[$i] = array_filter($words[$i]); $words[$i] = array_values($words[$i]);
			}
			//STEMMING AND TAGGING -- source : sastrawi (github)
			for($i=0;$i<$filecount;$i++){
				$sum_words = count($words[$i]);
				for ($j=0;$j<$sum_words;$j++){
					$words[$i][$j] = $stemmer->stem(strtolower($words[$i][$j]));
				}
				$words[$i] = array_filter($words[$i]); $words[$i] = array_values($words[$i]);
			}
			//ANALYZING every words
			for ($i=0;$i<$filecount;$i++)
				$anal[$i] = array_count_values($words[$i]);
			//MATCHING with the keyword from user and create session of IT
			if (isset($_POST["keyword"])){
				$keyword = $_POST["keyword"];
			}
			$key_word = $keyword;
			$keyword = $stemmer->stem(strtolower($keyword));
			$keyword = preg_split('/\s+/', $keyword);
			for ($i=0;$i<$filecount;$i++){
				$match[$i] = 0;
				$sum_anal = count($anal[$i]);
				$sum_keyword = count($keyword);
				for ($j=0;$j<$sum_keyword;$j++){
					if (array_key_exists($keyword[$j], $anal[$i])){
						$bil = $anal[$i][$keyword[$j]];
						$match[$i]+=$bil;
					} else
						$match[$i]+=0;
				}
			}
			//SORTING FROM HIGHEST
			arsort($match);
			//DELETE EMPTY ARRAY
			for ($i=0;$i<count($match);$i++){
				if ($match[$i] == 0)
					unset($match[$i]);
			}
			/// CHECK IF VALUE FROM MATCH -> EMPTY
			$cek_keyword_null = true;
			foreach ($match as $key => $value) {
				if ($value != 0)
					$cek_keyword_null = false;
			}
			$match = array_filter($match);
			//SET SESSION FOR SORTING
			$_SESSION['title'] 		= $title;
			$_SESSION['match'] 		= $match;
			$_SESSION['keyword']    = $key_word;
			$_SESSION['pagging'] 	= "active";
		}
		else{
			if (isset($_SESSION['title']) && isset($_SESSION['match'])){	
				$title = $_SESSION['title'];
				$match = $_SESSION['match'];
				$cek_keyword_null = false;
			}
		}
		//END TIME - Pengukuran waktu
		$akhir = microtime(true);
		//CEK SESSION START
		function cekHome(){
			$url =  "//{$_SERVER['HTTP_HOST']}";
			$home_url = htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
			$home_url = $home_url."/SearchEngine/search.php";

			$url =  "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
			$escaped_url = htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );

			if ($escaped_url==$home_url){
				session_unset();
				session_destroy();
			}
		}
	?>
	<div id="search_col">
		<a href="./index.php">
			<img id="logo" src="logo_search.png" alt="Koogle">
		</a>
		<div id="wrap">
			<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"])."?page=1&";?>">
				<input id="search" type="text" name="keyword" placeholder="What're you looking for ?" value=<?= $_SESSION['keyword']; ?>>
				<input id="search_submit" type="submit" name="submit" value="Rechercher">
			</form>
		</div>
	</div>
	<div id="result">
		<?php
			if (isset($_SESSION["pagging"])){
				ini_set('display_errors','On');
		        error_reporting(E_ALL);
		        //CREATE array consist of titile and match with the key
		        $n = 0;
		        foreach ($match as $key => $value) {
		        	$post[] = array(
		        			'Title' 		=> $title[$key],
		        			'Match_key' 	=> $key,
		        			'Match_value'	=> $value,
		        		);
		        	$n++;
		        }
		        if (!$cek_keyword_null) {
		        	error_reporting(0);
		        	// PAGINATION OF POST
					include 'pagination.class.php';
					$pagination = new pagination($post, (isset($_GET['page']) ? $_GET['page'] : 1), 10);
					$pagination->setShowFirstAndLast(false);
					$pagination->setMainSeperator(' | ');
					$productPages = $pagination->getResults();
					if (count($productPages) != 0) {
			            echo $pageNumbers = '<div class="numbers">'.$pagination->getLinks($_GET).'</div>';
			            foreach ($productPages as $productArray) {
			             	echo '<div class="card"><a href="article.php?pages='.($productArray['Match_key']+1).'"><p><b>'.$productArray['Title'].'</b></p></a>';
			             	echo '<p>Jumlah keyword : '.$productArray['Match_value'].'</p></div>';
		            	}
		            	echo $pageNumbers;
		            	echo "<br>";
		            }
		        }
		        else
		        	echo "<center><h3>Keyword not found</h3><center>";
			}
		?>
	</div>
</body>
</html>