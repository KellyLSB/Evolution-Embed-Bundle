$videos=$data["video"];
		//die(var_dump($videos));
		foreach($videos as $video_temp){
			//var_dump($video_temp);
			if(strpos($video_temp,"youtube")){

				$tmp=explode("?",$video_temp);
				$query_string=explode("&",array_pop($tmp));
				foreach($query_string as $query_line){
					$temp=explode("=",$query_line);
					if($temp[0]=="v"){
						$parts=explode("=",$query_line);
						$vid=array_pop($parts);
						$data=array("video"=>$vid,"host"=>"youtube");
						$update->add_video($data);
					}
				}
			}
			if(strpos($video_temp,"youtu.be")){
				$parts=explode("/",$video_temp);
				$vid=array_pop($parts);
				$data=array("video"=>$vid,"host"=>"youtube");
				$update->add_video($data);
				
			}
			if(strpos($video_temp,"vimeo")){
				$parts=explode("/",$video_temp);
				$vid=array_pop($parts);
				if(is_numeric($vid)){
					$data=array("video"=>$vid,"host"=>"vimeo");
					$update->add_video($data);
				}
			}
			// Add photo album
			if($new && is_numeric(e::$session->data['temp_album_id'])) {
				$update->module_connect('cms.album', e::$session->data['temp_album_id'], CONN_DIR_OUTGOING, CONN_TYPE_SECONDARY);
				e::$session->data['temp_album_id'] = null;
			}

			

		}