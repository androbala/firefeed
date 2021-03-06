<?php

//Crawler definition
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

date_default_timezone_set("Asia/Calcutta");//Set timezone to India
$current_ts=date("Y/m/d H:i:s");
echo "Job Started on ".$current_ts."\n";

require_once(__DIR__ . '/vendor/autoload.php');//Autoload required API's

//DB connection
$client = new MongoDB\Client;
$movies_collection = (new MongoDB\Client)->firedb->movies;
$events_collection = (new MongoDB\Client)->firedb->events;
$tokens_collection = (new MongoDB\Client)->firedb->device_tokens;

$client = new Client();
$crawler = $client->request('GET', 'http://www.ticketnew.com/Movie-Ticket-Online-booking/C/Chennai');

$upcoming_movies_list=array();
$upcoming_movies_links=array();
$active_movies=array();
$key="";
$i=0;
//Crawler to get the upcoming movies details from ticket new website
$crawler->filter('div[id$="overlay-tab-coming-soon"]')->each(function (Crawler $node, $i) {
             
             $node->filter('div[class$="titled-cornered-block"]')->each(function (Crawler $node, $i) {
                 
                         $node->filter('h3,li')->each(function ($node) {
                         
                                   $content = $node->text();
                                   $item = trim($content);
                                   global $key;
                                   global $upcoming_movies_list;
                                   if ($item=="Tamil")
                                    {
                                       $key="Tamil";
                                    }
                                    else if($item=="English")
                                    {
                                        $key="English";
                                    }
                                    else if($item=="Hindi")
                                    {
                                        $key="Hindi";
                                    }
                                    else if($item=="Telugu")
                                    {
                                        $key="Telugu";
                                    }else if($item=="Malayalam")
                                        {
                                            $key="Malayalam";
                                        }
                                        else{
                                       $upcoming_movies_list[$key][] = $item;}
                         });
                //to get link for respective language movies
                $node->filter('a')->each(function (Crawler $node){ 
                    global $upcoming_movies_links;
                    $link = $node->link();
                    $uri = $link->getUri();
                    $upcoming_movies_links[] = $uri;
                }); 
      
        }); 
            
});
//Inserting upcoming movies details into database
$current_ts = date("Y/m/d H:i:s");
$movie_name = $movie_link = $lang = $actor = $movie_id = $director = $music ="";

foreach($upcoming_movies_list as $key=>$values)
    {
        $lang=$key; //sets language as key of the array
        foreach ($values as $key => $value)
        {
            $cast_crew=array();
            $movie_name=$value; // sets movie name
            $active_movies[$i]=$movie_name;
            $i +=1;
            $temp_name=str_replace(" ","-",$movie_name); //temporary variable to get the link of the movie from the array
            foreach($upcoming_movies_links as $link) 
            {
                $movie_link="";
                if (strpos($link, $temp_name) !== false)
                {
                    $movie_link=$link;
                    break; //break if the link is assigned
                }
            }
            if(empty($movie_link))
            {
                $movie_link="Link Not Available";
            }
            else
            {
                $temp_id=explode("/",$movie_link);
                $movie_id=$temp_id[5];
                $poster_url="http://cdn.in.ticketnew.com/Movie/".$movie_id."/m1.jpg";
                $crawl_link="http://www.ticketnew.com/".$temp_name."-Movie-Tickets-Online-Show-Timings/Online-Advance-Booking/".$temp_id[5]."/C/Chennai";
                $crawler = $client->request('GET', $crawl_link);
				//Crawler to get the synopsis of the movies
				$crawler->filter('div[class$="movie-info-synopsis"]')->each(function (Crawler $node, $i) {
                     $node->filter('td')->each(function ($node) {
                         global $cast_crew;
                         global $key;
                         $value= $node->text();
                         $temp=explode('\n',$value);
                         foreach($temp as $values)
                         {
                             $value=trim($values);
                             if($value=="Genre") 
                                {
                                    $key=$value;
                                }
                                elseif($value=="Language") 
                                {
                                    $key=$value;
                                }
                                elseif($value=="Movie Producer") 
                                {
                                    $key="Producer";
                                }
                                elseif($value==":") 
                                {
                                    
                                }
                                elseif(strpos($value, "Release") !== false) 
                                {
                                    $key="Release";
                                    
                                }
                                else
                                {
                                    $cast_crew[$key][]=$value;
                                }
                             }
                         
                     });
                });
				$genre=$cast_crew["Genre"];
                $producer=$cast_crew["Producer"];
                $release_ts=$cast_crew["Release"][0];
                unset($cast_crew);
				$cast_crew=array();
				//Crawler to get the cast and crew details
                $crawler->filter('div[class$="movie-info-description"]')->each(function (Crawler $node, $i) {
                     $node->filter('p')->each(function ($node) {
                         global $cast_crew;
                         $value= $node->text();
                         $temp=explode('\n',$value);
                         foreach($temp as $value)
                         {
                             $first=explode(':',$value);
                             foreach($first as $value)
                             {
                                 $value=trim($value);
                                if($value=="Actors") 
                                {
                                    $key=$value;
                                }
                                elseif($value=="Director") 
                                {
                                    $key=$value;
                                }
                                elseif($value=="Music director") 
                                {
                                    $key=$value;
                                }
                                else
                                {
                                    $cast_crew[$key][]=$value;
                                }
                             }
                         }
                     });
                });
                $actor=$cast_crew["Actors"];
                $director=$cast_crew["Director"];
                $music=$cast_crew["Music director"];
                unset($cast_crew);
            }
            
            $flag=isPresent($movie_name,$movies_collection);
            $type="upcoming";
            if($flag)
            {
                $result = $movies_collection->updateOne(
                ['name' => $movie_name],
                ['$set' => array("lang"=> $lang , "name" => $movie_name,"release_ts"=>$release_ts, "update_ts" => $current_ts)],
                ['upsert' => true]);
            }
            else
            {
                $result = $movies_collection->insertOne(
                array("lang"=> $lang , "name" => $movie_name,"poster_url"=>$poster_url, "type" => $type, "id"=>$movie_id,"link"=>$movie_link,"actors"=>$actor,"director"=>$director,"music_director"=>$music,"genre"=>$genre,"producer"=>$producer,"release_ts"=>date("Y/m/d H:i:s",strtotime($release_ts)),"disabled"=>"false","insert_ts" => $current_ts ));
                
                $events = $events_collection->insertOne(
                array("movie_name"=>$movie_name,"event_type" => "FU","notify"=> 'true',"insert_ts" => $current_ts ));
            }

        }
    }

$running_movies_list=array();
$running_movies_links=array();
$key="";
//Crawler to get the running movies details from ticket new website
$crawler->filter('div[id$="overlay-tab-booking-open"]')->each(function (Crawler $node, $i) {
             
             $node->filter('div[class$="titled-cornered-block"]')->each(function (Crawler $node, $i) {
                     
                        $node->filter('h3,li')->each(function ($node) {
                               
                                $content = $node->text();
                                $item = trim($content);
                                global $key;
                                global $running_movies_list;
                                       
                                if ($item=="Tamil")
                                {
                                   $key="Tamil";
                                }
                                else if($item=="English")
                                {
                                    $key="English";
                                }
                                else if($item=="Hindi")
                                {
                                    $key="Hindi";
                                }
                                else if($item=="Telugu")
                                {
                                    $key="Telugu";
                                }
                                else if($item=="Malayalam")
                                {
                                    $key="Malayalam";
                                }
                                else{
                                $running_movies_list[$key][] = $item;}
                        });
                        //to get link for respective language movies
                        $node->filter('a')->each(function (Crawler $node){ 
                        global $key;
                        global $running_movies_links;
                        $link = $node->link();
                        $uri = $link->getUri();
                        $running_movies_links[] = $uri;
                        }); 
            }); 
});
//Inserting running movies details into database
$current_ts = date("Y/m/d H:i:s");

$movie_name = $movie_link = $lang = $actor = $movie_id = $director = $music ="";

foreach($running_movies_list as $key=>$values)
    {
        $lang=$key; //sets language as key of the array
        foreach ($values as $key => $value)
        {
            $cast_crew=array();
            $movie_name=$value; // sets movie name
            $active_movies[$i]=$movie_name;
            $i +=1;
            $temp_name=str_replace(" ","-",$movie_name); //temporary variable to get the link of the movie from the array
            foreach($running_movies_links as $link) 
            {
                $movie_link="";
                if (strpos($link, $temp_name) !== false)
                {
                    $movie_link=$link;
                    break; //break if the link is assigned
                }
            }
            if(empty($movie_link))
            {
                $movie_link="Link Not Available";
            }
            else
            {
                $temp_id=explode("/",$movie_link);
                $movie_id=$temp_id[5];
                $poster_url="http://cdn.in.ticketnew.com/Movie/".$movie_id."/m1.jpg";
                $crawler = $client->request('GET', $movie_link);
				//Crawler to get the synopsis of the movies
				$crawler->filter('div[class$="movie-info-synopsis"]')->each(function (Crawler $node, $i) {
                     $node->filter('td')->each(function ($node) {
                         global $cast_crew;
                         global $key;
                         $value= $node->text();
                         $temp=explode('\n',$value);
                         foreach($temp as $values)
                         {
                             $value=trim($values);
                             if($value=="Genre") 
                                {
                                    $key=$value;
                                }
                                elseif($value=="Language") 
                                {
                                    $key=$value;
                                }
                                elseif($value=="Movie Producer") 
                                {
                                    $key="Producer";
                                }
                                elseif($value==":") 
                                {
                                    
                                }
                                elseif(strpos($value, "Release") !== false) 
                                {
                                    $key="Release";
                                    
                                }
                                else
                                {
                                    $cast_crew[$key][]=$value;
                                }
                             }
                         
                     });
                });
				$genre=$cast_crew["Genre"];
                $producer=$cast_crew["Producer"];
                $release_ts=$cast_crew["Release"][0];
                unset($cast_crew);
				$cast_crew=array();
				//Crawler to get the cast and crew details
                $crawler->filter('div[class$="movie-info-description"]')->each(function (Crawler $node, $i) {
                     $node->filter('p')->each(function ($node) {
                         global $cast_crew;
                         $value= $node->text();
                         $temp=explode('\n',$value);
                         foreach($temp as $value)
                         {
                             $first=explode(':',$value);
                             foreach($first as $value)
                             {
                                $value=trim($value);
                                if($value=="Actors") 
                                {
                                    $key=$value;
                                }
                                elseif($value=="Director") 
                                {
                                    $key=$value;
                                }
                                elseif($value=="Music director") 
                                {
                                    $key=$value;
                                }
                                else
                                {
                                    $cast_crew[$key][]=$value;
                                }
                             }
                         }
                     });
                });
                $actor=$cast_crew["Actors"];
                $director=$cast_crew["Director"];
                $music=$cast_crew["Music director"];
                unset($cast_crew);
                
            }
            
            $upcoming=isUpcoming($movie_name,$movies_collection);
            $type="running";
            if($upcoming)
            {
                $result = $movies_collection->updateOne(
                ['name' => $movie_name],
                ['$set' => array("lang"=> $lang , "name" => $movie_name, "type" => $type, "prev_type" => "upcoming","booking_open_ts"=>$current_ts, "notify" => "true", "update_ts" => $current_ts )],
                ['upsert' => true]
                );
                
                $events = $events_collection->insertOne(
                array("movie_name"=>$movie_name,"event_type" => "UR","notify"=> 'true',"insert_ts" => $current_ts ));
            }
            else
            {
                $running=isRunning($movie_name,$movies_collection);
                
                if($running)
                {
                   $result = $movies_collection->updateOne(
                    ['name' => $value],
                    ['$set' => array("lang"=> $lang , "name" => $value, "type" => $type, "update_ts" => $current_ts )],
                    ['upsert' => true]);
                }
                else
                {
                   $result = $movies_collection->insertOne(
                   array("lang"=> $lang , "name" => $movie_name, "type" => $type, "id"=>$movie_id,"poster_url"=>$poster_url,"link"=>$movie_link,"actors"=>$actor,"director"=>$director,"music_director"=>$music,"genre"=>$genre,"producer"=>$producer,"release_ts"=>date("Y/m/d H:i:s",strtotime($release_ts)), "notify" => "true","disabled"=>"false", "insert_ts" => $current_ts ));
                
                    $events = $events_collection->insertOne(
                    array("movie_name"=>$movie_name,"event_type" => "FR","notify"=> 'true',"insert_ts" => $current_ts ));
            
                }
            }
        }
    }

//Common functions

function isUpcoming($value,$collection)
{
     $count = $collection->count(["name"=>$value,"type"=>"upcoming"]);
     if($count>0) 
     {
         return true;
     }
     else
     {
         return false;
     }
}


function isRunning($value,$collection)
{
     $count = $collection->count(["name"=>$value,"type"=>"running"]);
     if($count>0) 
     {
         return true;
     }
     else
     {
         return false;
     }
}

function isPresent($value,$collection)
{
     $count = $collection->count(["name"=>$value]);
     if($count>0) 
     {
         return true;
     }
     else
     {
         return false;
     }
}

//Snippet for updating the closed movies and instering events
$db_movies=array();
$result = $movies_collection->find(array('type' => array('$in' => array("running","upcoming"))));
$i=0;
foreach($result as $document)
{
    $temp = json_encode($document);
    $json = json_decode($temp , true);
    
    $db_movies[$i]=$json["name"];
    $i +=1;
}
$i=0;
$inactive_movies=array_diff($db_movies,$active_movies);

foreach ($inactive_movies as $name) {
    
    $type="closed";
        $movies = $movies_collection->updateOne(
            ['name' => $name],
            ['$set' => array("type" => $type, "disabled"=>'true', "close_ts" => $current_ts)],
            ['upsert' => true]);
        
        $events = $events_collection->insertOne(
            array("movie_name"=>$name,"event_type" => "RC","notify"=> 'true',"insert_ts" => $current_ts ));

}

//Notification for the new events added

$yourApiSecret = "f14f6029e3952e2e9ccc79bbfc60fdfbb6d123497c6a35e6:";
$androidAppId = "4cff0232";

$token = $tokens_collection->find();
$i=0;
$device_tokens=array();
foreach($token as $document)
{
  $temp = json_encode($document);
  $json = json_decode($temp , true);
  $device_tokens[$i]=$json["token_id"];
  $i +=1;
}

$event_type="";
$events = $events_collection->find(array('notify' =>"true"));

foreach($events as $document)
{
  
    $temp = json_encode($document);
    $json = json_decode($temp , true);
    if($json["event_type"]=="RC")
    {
      $event_type=$json["movie_name"]." movie is closed for booking";
    }
    elseif($json["event_type"]=="FU")
    {
      $event_type=$json["movie_name"]." has been added to Upcoming";
    }
    elseif($json["event_type"]=="UR")
    {
      $event_type=$json["movie_name"]." movie booking has been opened";
    }
    elseif($json["event_type"]=="FR")
    {
      $event_type=$json["movie_name"]." movie booking has been opened";
    }
    
    $data = array(
      "tokens" => $device_tokens,
      "notification" => ["alert"=>$event_type]
        );
    $data_string = json_encode($data);
    $ch = curl_init('https://push.ionic.io/api/v1/push');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'X-Ionic-Application-Id: '.$androidAppId,
        'Content-Length: ' . strlen($data_string),
        'Authorization: Basic '.base64_encode($yourApiSecret)
        )
    );
    
    $result = curl_exec($ch);
   
    $test = $events_collection->updateOne(
            ['movie_name' => $json["movie_name"]],
            ['$set' => array("notify"=>"done","notified_ts" => $current_ts)],
            ['upsert' => true]); 
}


echo "Job completed on ".$current_ts."\n";


?>