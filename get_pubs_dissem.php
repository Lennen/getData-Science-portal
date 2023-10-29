<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'config_link.php';

/////////1. GET_SCOPUS_AUTHOR//////////
$result = $mysqli->query("SELECT id_scopus,author_last,author_name_filtered FROM user_scopus_orcid_id");
    $arr = array();
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        foreach ($row as $key=>$value) {   
            $scopus_authors[$key][] = $value;
        }    
    }
    $result->close();

print_r("Scopus author: ");
print_r($scopus_authors['author_last'][0]." ".$scopus_authors['author_name_filtered'][0]);

/////////2. GET_BEARER//////////
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.mendeley.com/oauth/token',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'grant_type=client_credentials&scope=all&client_id=10413&client_secret=VYVNRhInIlhlZpLg',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/x-www-form-urlencoded'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
$json = json_decode($response);
$bearer = $json->access_token;
echo("<br/>Bearer token: ".$bearer."<br/>");


//Для каждого указанного Scopus ID
foreach($scopus_authors['author_last'] as $key=>$current_scopus_author_last){
	$current_scopus_author_name = $scopus_authors['author_name_filtered'][$key];
	$id_scopus = $scopus_authors['id_scopus'][$key];
	/////////3. GET_AUTHOR_FIO//////////
	/*
	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://api.mendeley.com/profiles/v2?scopus_author_id='.$current_scopus_id,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'GET',
	  CURLOPT_HTTPHEADER => array(
		'Authorization: Bearer '.$bearer
	  ),
	));

	$response = curl_exec($curl);

	curl_close($curl);
	$json = json_decode($response);

	$first_name = $json[1]->first_name;
	$last_name = $json[1]->last_name;
	print_r('Researcher Full name: '.$first_name.' '.$last_name.'<br/>');
	*/

	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://dissem.in/api/search/?authors='.$current_scopus_author_name.'%20'.$current_scopus_author_last,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'GET',
	));

	$response = curl_exec($curl);

	curl_close($curl);
	$json = json_decode($response);

	echo('<br/>Articles Info: <br/>');
	$papers_list = $json->papers;
	//print_r($papers_list);

	echo("<br/><br/>");
	foreach($papers_list as $key =>$value){
		echo("<center><b>Публикация №");
		print_r($key+1);
		echo(":</b></center><br/>");
		echo("Название: ");
		$title = $value->title;
		$title = str_replace('\'', '`', $title);
		echo($title."<br/>");
		echo("Дата публикации: ");
		echo($value->date."<br/>");
		$phpdate = strtotime( $value->date );
		$Y = date( 'Y', $phpdate );

		
		echo("<br/>");
		$author = $value->authors; 
		$authors = "";
		foreach($author as $key=>$val){
				echo("Автор:<br/>");
				echo("Имя: ");
				print_r($val->name->first);
				echo("<br/>");
				echo("Фамилия: ");
				print_r($val->name->last);
				echo("<br/><br/>");
				$nfirst = $val->name->first;
				$nlast = $val->name->last;
				
				if ($key==0){
					$nfirstMain = str_replace('\'', '`', $nfirst);
					$nlastMain = str_replace('\'', '`', $nlast);
				
				$mainAuthor = $nlastMain." ".$nfirstMain;
				} else{
					$nfirst = str_replace('\'', '`', $nfirst);
					$nlast = str_replace('\'', '`', $nlast);
					
					$author_current = $nfirst." ".$nlast;
					$authors = $authors."".$author_current."; ";
				}
				
				if($key >10){
					break;
				}
		}
		
		echo("Ссылка на PDF: ");
		if ($value->records[0]->pdf_url) {
			$link_full_text = $value->records[0]->pdf_url;
			echo($link_full_text."<br/><br/>");
		} else {
			$link_full_text = $value->records[1]->pdf_url;
			echo($link_full_text."<br/><br/>");
		}
		
		echo("Издательство: ");
		if ($value->records[0]->publisher) {
			$publisher = $value->records[0]->publisher;
			echo($publisher."<br/><br/>");
		} else {
			$publisher = $value->records[1]->publisher;
			echo($publisher."<br/><br/>");
		}
		$publisher = str_replace('\'', '`', $publisher);
		
		echo("Журнал: ");
		if ($value->records[0]->journal) {
			$journal = $value->records[0]->journal;
			echo($journal."<br/><br/>");
		} else {
			$journal = $value->records[1]->journal;
			echo($journal."<br/><br/>");
		}
		$journal = str_replace('\'', '`', $journal);
		
		echo("Аннотация: ");
		$abstract = $value->records[0]->abstract;
		$abstract = str_replace('\'', '`', $abstract);
		echo($abstract."<br/><br/>");
		echo("Ключевые слова: ");
		if ($value->records[0]->keywords) {
			$keywords = $value->records[0]->keywords;
			echo($keywords."<br/>");
		} else {
			$keywords = $value->records[1]->keywords;
			echo($keywords."<br/>");
		}
		$keywords = str_replace('\'', '`', $keywords);
		
		echo("DOI: ");
		if ($value->records[0]->doi) {
			$doi = $value->records[0]->doi;
			echo($doi."<br/><br/>");
		} else {
			$doi = $value->records[1]->doi;
			echo($doi."<br/><br/>");
		}
	
		echo("Бибзапись: ");
		$bibzapis = $nlastMain.", ".$nfirstMain." (".$Y."). ".$title." / ".$publisher.", ".$journal; 
		echo($bibzapis."<br/><br/>");
	
		echo("<br/>Без парсинга: <br/>");
		print_r($value);
		echo("<br/><br/>");
	
		//Год может быть неверный
		if($Y >=1700 && $Y <= 2021){
		} else {
			$Y = 0;
		}
	 
		
		//Сохраняем в БД строки с публикациями данного автора
		$result = $mysqli->query("SELECT title FROM user_pub_source_23 WHERE title='$title' AND author='$mainAuthor' LIMIT 1");
        $double_record = $result->fetch_array(MYSQLI_ASSOC);
        if(!isset($double_record)){
			$query_ = "INSERT INTO user_pub_source_22 
			(doi, bib_simhash, id_profile_r2, id_profile_source, page_pub, id_type, link_full_text, title, abstract, abstract_ru, bibzapis, classif, keywords, subauthors, Y, cit, author) 
			VALUES ('$doi', '', '$id_scopus', '', '', '1', '$link_full_text', '$title', '$abstract','', '$bibzapis', '', '$keywords', '$authors', '$Y', '-1', '$mainAuthor')";              
			$zapros_ = $mysqli->query($query_);
			if (!$zapros_) exit ($mysqli->error);
		}
		
	}
	
}