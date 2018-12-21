<?php

class Diary_model extends CI_Model
{
    protected $diary_table = 'diary';

    /**
     * Use registration merchant
     * @param: {array} merchant data
     */
    public function insert_diary(array $data)
    {
        $this->db->insert($this->diary_table, $data);
        return $this->db->insert_id();
    }

    public function insert_gallery(array $data)
    {
        $this->db->insert('gallery', $data);
        return $this->db->insert_id();
    }

    public function edit_diary(array $data, $id_diary){
        $query = $this->db->where('id_diary',$id_diary)
                            ->set($data)
                            ->update('diary');
        return true;
                            
    }

    public function viewbyuser($id_user){
        $query = $this->db->select("*")
                        ->where('id_user',$id_user)
                        ->order_by('id_diary','DESC')
                        ->get('diary');

        $data = null;
        foreach($query->result() as $row){
            if($row->image != NULL){
                $img = $row->image;
            }else{
                $img = "http://192.168.43.79/myDiary/image/default.jpg";   
            }
            $data [] = [
            'id_diary' => $row->id_diary,
            'title' => $row->title,
            'diary' => $row->diary,
            'image' => $img
            ];
        }

        return $data;
    }

    public function viewall(){
        $query = $this->db->get('diary');

        $data = null;
        foreach($query->result() as $row){
            if($row->image != NULL){
                $img = $row->image;
            }else{
                $img = "http://192.168.43.79/myDiary/image/default.jpg";   
            }
            $data [] = [
            'id_diary' => $row->id_diary,
            'title' => $row->title,
            'diary' => $row->diary,
            'image' =>$img
            ];
        }

        return $data;
    }

    public function viewbydiary($id_diary){
        $query = $this->db->get_where('diary',array('id_diary'=>$id_diary));

        $data = null;
        foreach($query->result() as $row){
            if($row->image != NULL){
                $img = $row->image;
            }else{
                $img = "http://192.168.43.79/myDiary/image/default.jpg";   
            }
            $data = [
            'title' => $row->title,
            'diary' => $row->diary,
            'image' => $img
            ];
        }

        return $data;
    }

    public function delete($id_diary){
        $this->db->where('id_diary',$id_diary);
        $this->db->delete('diary');
        return true;
    }
    
}
?>