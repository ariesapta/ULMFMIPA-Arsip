<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Folder extends MY_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
    protected $stackPath=array();
    public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->helper('url');
		$this->load->model('FolderModel','folder');
                $this->load->model('ArsipModel','arsip');
                $this->load->model('FolderInArsipModel','inarsip');
                $this->load->model('OrganisasiModel');
                $this->load->model('UserModel','user');
	}
    public function index()
    {
         
        if($this->input->is_ajax_request()){
            $request = $this->input->get();
            $where["parent_id"]=null;
            $data = $this->folder->getDataGrid($request,
                    'folder.folder_id, folder.nama, unit.nama as unit, folder.tgl_buat',
                    $where,
                    'left');
           // echo $this->db->last_query();
            echo json_encode($data);
        }else{
            $this->load->library('form_validation');
            $result['unit_kerja']=$this->OrganisasiModel->getListData('bidang_id','nama');
            $this->load->view('templates/header_list');
            $this->load->view('folder/list',$result);
            $this->load->view('templates/footer_list');
        }
		
		
		
    }
    public function detail($id)
    {
         
        if($this->input->is_ajax_request()){
            $request = $this->input->get();
            $where["parent_id"]=null;
            $data = $this->folder->getDataGrid($request,
                    'folder.folder_id, folder.nama, unit.nama as unit, folder.tgl_buat',
                    $where,
                    'left');
           // echo $this->db->last_query();
            echo json_encode($data);
        }else{
            $this->load->library('form_validation');
            $result["folder"] = $this->folder->getByPrimary($id,'*',true);
            $result["tabel"] = $this->folder->createTabel($id);
            $result["group_id"] = $id;
            //print_r($result);
            $this->load->view('templates/header_list');
            $this->load->view('folder/detail',$result);
            $this->load->view('templates/footer_list');
        }
		
		
		
    }
    
    // Additional Methods
        public function getUserList($bidang=null)
	{
            $where["bidang_id"]=$bidang;
            $result_user=$this->user->getListDataModified('id','nama',$where);
            //print_r($result_user);
            $option="";
            foreach ($result_user as $row=>$value){
                $option.="<option value='".$row."'>".$value."</option>";
            }
            echo $option;
	}
    
    public function simpan($id = null){
        
            $model = $this->folder->getByPrimary($id);
            $id=$model["folder_id"];
            if($this->input->post()){    
                $model_input["nama"] = $this->input->post("nama");
                $model_input["pemilik_id"]= $this->session->user["id"];
                $model_input["tgl_buat"]=date("Y-m-d H:i:s");
                $viewer=json_decode($this->input->post("viewer_folder"));
                $this->load->library('form_validation');
                $this->form_validation->set_rules($this->folder->rules("tetap"));
                $this->form_validation->set_data($model_input);
                if ($this->form_validation->run()){
                    if($model['folder_id']!==null){
                        if ($this->folder->update($id,$model_input)){
                            $this->folder->addUserInViewerFolder($id,$viewer);
                            $response["simpan"]=true;
                            $response["pesan"]="Folder berhasil diupdate.";
                        }
                    }else{
                        $this->form_validation->set_rules($this->folder->rules());
                        if ($this->form_validation->run()){
                            if ($this->folder->insertCustom($model_input,$viewer)){
                                $response["simpan"]=true;
                                $response["pesan"]="Folder berhasil dibuat.";
                            }
                        } else {
                            $response['simpan'] = false;
                            $response['pesan'] = validation_errors();
                        }    
                    }
                }else{
                    $response['simpan'] = false;
                    $response['pesan'] = validation_errors();
                }
                echo json_encode($response);
            }else{
                $response['model'] = $model;
                $response['viewer'] = $this->folder->getUserInViewerFolder($id);
                $response['simpan'] = true;
                echo json_encode($response);
            }
//        }
    }
    
    public function detailsimpan($id = null){
        
            $model = $this->folder->getByPrimary($id);
            $id=$model["folder_id"];
            if($this->input->post()){    
                $model_input["nama"] = $this->input->post("nama");
                $model_input["pemilik_id"]= $this->session->user["id"];
                $model_input["tgl_buat"]=date("Y-m-d H:i:s");
                $model_input["parent_id"]=$this->input->post("parent_id");
                $model_input["group_id"]=$this->input->post("group_id");
                $this->load->library('form_validation');
                $this->form_validation->set_rules($this->folder->rules("tetap"));
                $this->form_validation->set_data($model_input);
                if ($this->form_validation->run()){
                    if($model['folder_id']!==null){
                        if ($this->folder->update($id,$model_input)){
                            $response["simpan"]=true;
                            $response["pesan"]="Folder berhasil diupdate.";
                            $response["table"]= $this->populate_folder($model_input["group_id"]);
                        }
                    }else{
                        $this->form_validation->set_rules($this->folder->rules());
                        if ($this->form_validation->run()){
                            $where["folder_id"]=$model_input["parent_id"];
                            if (!$this->inarsip->existAttr($where)){
                                //echo $this->db->last_query();
                                $insert=$this->folder->insertGetLastId($model_input);
                                if ($insert>0){
                                    $response["simpan"]=true;
                                    $response["pesan"]="Folder berhasil dibuat.";
                                    $response["table"]=$this->populate_folder($model_input["group_id"]);
                                }
                            } else {
                                $response['simpan'] = false;
                                $response['pesan'] = "Tidak dapat membuat sub folder baru. Folder ini telah dipakai diarsip.";
                            }
                        } else {
                            $response['simpan'] = false;
                            $response['pesan'] = validation_errors();
                        }    
                    }
                }else{
                    $response['simpan'] = false;
                    $response['pesan'] = validation_errors();
                }
                echo json_encode($response);
            }
//        }
    }
    
    function tabel_subfolder(){
        $id=$this->input->post("folder_id");
        echo "<table class='table table-bordered table-striped table-hover js-basic-example dataTable'>";
            echo "<tr>";
                echo "<td width=90%>Folder</td>";
                echo "<td>Aksi</td>";
            echo "</tr>";
            echo $this->create_folder($id);
        echo "</table>";
    }
            
    function populate_folder($id){
        
        $tabel = $this->folder->createTabel($id);
        $tbl="";
        foreach ($tabel->result() as $row){
            $width=100;
            $aksi="";
            if (abs($row->stack_top)>1){
                $width=100-(abs($row->stack_top)*4);
                $aksi='<a onclick="edit_modal('.$row->emp.')"><i class="material-icons">edit</i></a><a onclick="delete_modal('.$row->emp.')"><i class="material-icons">delete_forever</i></a>';
            }
            $padding=$width."px";
            $w=$width."%";
            $btnLihat="";
            if (($row->rgt-$row->lft)==1){
                $btnLihat='<a href="'. site_url("folder/arsip_selected/").$row->emp.'"><i class="material-icons">remove_red_eye</i></a>';
            }
        
            $tbl.='                    
                                    <tr>
                                        <td>
                                          
                                                   <a onclick="add('.$row->emp.')"><i class="material-icons">create_new_folder</i></a>
                                                   '.$aksi.'
                                                
                                           
                                        </td>
                                    <td>
                                       
                                           
                                            <div style="left:'.$padding.';width:'.$w.';float:right">'.$row->nama_folder.'</div>
                                        
                                    </td>
                                    <td>
                                         '.$btnLihat.'
                                    </td>
                                    <td>'.$row->total.'</td>
                                </tr>';
        }
        //echo "<table>";
        return $tbl;
        //echo "</table>";
    }
    function create_folder($id){
        
        $tabel = $this->folder->createTabel($id);
        $tbl="";
        foreach ($tabel->result() as $row){
            $width=100;
            if (abs($row->stack_top)>1){
                $width=100-(abs($row->stack_top)*4);
            }
            $padding=$width."px";
            $w=$width."%";
            
            $btnLihat="";
            if (($row->rgt-$row->lft)==1){
                $btnLihat='<a class="btn btn-primary" onclick="addFolder('.$row->emp.')">add</a>';
            }
        
            $tbl.='                    
                                <tr>
                                       
                                    <td>
                                        <div style="width:'.$w.';float:right">'.$row->nama_folder.'</div>
                                        
                                    </td>
                                    <td>
                                         '.$btnLihat.'
                                    </td>
                                </tr>';
        }
        //echo "<table>";
        return $tbl;
        //echo "</table>";
    }
    public function hapusdetail(){

            if($this->input->is_ajax_request()){
                $id = $this->input->get('id');
                $model = $this->folder->getByPrimary($id,'*',true,true);
                $where["folder_id"]=$id;
                $parent=$this->inarsip->existAttr($where);
                if (isset($model["folder_id"]) && $parent){
                    $response['hapus'] = false;
                    $response['pesan'] = "Folder gagal dihapus. Masih ada file didalamnya.";
                    
                } else {
                    if($this->folder->delete($id))
                    {
                        $response['hapus'] = true;
                        $response['pesan'] = "Folder berhasil dihapus.";
                        $response["table"]=$this->populate_folder($model["group_id"]);
                    }
                    else
                    {
                        $response['hapus'] = false;
                        $response['pesan'] = "Folder gagal dihapus.";
                    }
                    
                }    
                echo json_encode($response);
            }
    }
    public function hapus(){

            if($this->input->is_ajax_request()){
                $id = $this->input->get('id');
                $model = $this->folder->getByPrimary($id,'*',true,true);
                $where["parent_id"]=$id;
                $parent=$this->folder->existAttr($where);
                if (isset($model["folder_id"]) && $parent){
                    $response['hapus'] = false;
                    $response['pesan'] = "Folder gagal dihapus. Masih ada folder didalamnya.";
                    
                } else {
                    if ($model["pemilid_id"]==$this->sesion->user["id"]){
                        if($this->folder->delete($id))
                        {
                            $response['hapus'] = true;
                            $response['pesan'] = "Folder berhasil dihapus.";
                        }
                        else
                        {
                            $response['hapus'] = false;
                            $response['pesan'] = "Folder gagal dihapus.";
                        }
                    } else {
                        $response['hapus'] = false;
                        $response['pesan'] = "Folder gagal dihapus. Anda bukan pemilik folder ini.";
                    }
                    
                }    
                echo json_encode($response);
            }
    }
    function getPath($id){
        $this->getPathDo($id);
        //print_r($this->stackPath);
        for ($i=sizeof($this->stackPath)-1;$i>=0;$i--){
            if ($i==0)
                $panah=""; else $panah=" > ";
            echo $this->stackPath[$i].$panah;
        }
    }
    
    function getPathDo($id){
        
        $ambiljumlah=$this->folder->getByPrimary($id,'*',true);
        
        array_push($this->stackPath, $ambiljumlah["nama"]);
        if ($ambiljumlah["parent_id"]!=null){
        $this->getPathDo($ambiljumlah["parent_id"]);
        }
        
        
    }
    
    function arsip_selected($id){
        if($this->input->is_ajax_request()){
            $request = $this->input->get();
            $data = $this->FolderInArsipModel->getDataGrid($request,
                    'arsip.id,arsip.nama_file, arsip.isi, kat.nama as nama_kategori, unit.nama as nama_unit');
            //echo $this->db->last_query();
            echo json_encode($data);
        }else{
            $this->load->library('form_validation');
            $result["folder"] = $this->folder->getByPrimary($id,'*',true);
            $this->load->view('templates/header_list');
            $this->load->view('folder/arsip_list',$result);
            $this->load->view('templates/footer_list');
        }
    }
}