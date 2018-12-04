<?php

class User_model extends CI_Model
{
    protected $user_table = 'user';

    /**
     * Use registration merchant
     * @param: {array} merchant data
     */
    public function insert_user(array $data)
    {
        $this->db->insert($this->user_table, $data);
        return $this->db->insert_id();

    }

    public function update_user($id_user, $data) {
        $this->db->set($data);
		$this->db->where('id_user',$id_user);
        $this->db->update('user'); 
        return true;
    }

    public function view(){
        $query = $this->db->get('user');
        $data = NULL;
        foreach ($query->result() as $row)
        {
            $data[]=[
                'id_user' => $row->id_user,
                'name' => $row->name,
                'username' => $row->username,
                'email' => $row->email
            ];
        }
        return $data;
    }

    public function viewbyuser($id_user){
        $query = $this->db->get_where('user',array('id_user'=>$id_user));
        $data = NULL;
        foreach ($query->result() as $row)
        {
            $data[]=[
                'id_user' => $row->id_user,
                'name' => $row->name,
                'username' => $row->username,
                'email' => $row->email
            ];
        }
        return $data;
    }

    public function verification($code) 
    {
        $query = $this->db->set('status',1)
		                    ->where('code',$code)
                            ->update('user'); 
        return $this->db->affected_rows();
    }

    public function login($username, $password)
    {
        $this->db->where('username', $username);
        $this->db->where('password', md5($password));
        $this->db->select('*');

        return $this->db->get('user')->result();
       
    }

    public function list_merchant(){
        $query = $this->db->get('merchant');
        $merchant_list = NULL;
        foreach ($query->result() as $row)
        {
            $merchant_list[]=[
                'id_merchant' => $row->id_merchant,
                'id_user' => $row->id_user,
                'merchantname' => $row->merchantname,
                'merchantphone' => $row->merchantphone,
                'merchantaddress' => $row->merchantaddress,
                'merchantdesc' => $row->merchantdesc,
                'merchantnote' =>$row->merchantnote,
                'merchantcreatedate'=>$row->merchantcreatedate,
                'ktpimage' => $row->ktpimage,
                'isvalidseller' => $row->isvalidseller,
                'merchantimage' =>$row->merchantimage,
                'merchantwall' =>$row->merchantwall
            ];
        }
        return $merchant_list;
    }

    public function delete_merchant($id_merchant){
        $this->db->where('id_merchant',$id_merchant);
        $this->db->set('isvalidseller',0);
        $this->db->update('merchant');
        return $this->db->affected_rows();
    }

    public function verify_merchant($id_merchant){
        $this->db->where('id_merchant',$id_merchant);
        $this->db->set('isvalidseller',1);
        $this->db->update('merchant');
        return $this->db->affected_rows();
    }

    

    public function loginUser($username, $password)
    {
        $this->db->where('userstatus', 1);
        $this->db->where('username', $username);
        $this->db->where('isuserverified',1);
        $this->db->where('userpassword', md5($password));
        $this->db->limit(1);

        return $this->db->get('user')->result();
       
    }

    public function image_merchant($image_merchant,$id_merchant) {
        $this->db->set('merchantimage',$image_merchant);
		$this->db->where('id_merchant',$id_merchant);
        $this->db->update('merchant'); 
        return true;
    }

    public function wall_merchant($image_merchant,$id_merchant) {
        $this->db->set('merchantwall',$image_merchant);
		$this->db->where('id_merchant',$id_merchant);
        $this->db->update('merchant'); 
        return true;
    }

    public function dailytrx($id_seller){
        $query = $this->db->like('orderdate', date('Y-m-d'))
                ->where('id_user',$id_seller)
                ->where('orderstatus','paid')
                ->get('order');
         $daily_data = NULL;
         foreach ($query->result() as $row)
         {
             $daily_data[]=[
                 'id_order' => $row->id_order,
                 'id_user' => $row->id_user,
                 'id_address' => $row->id_address,
                 'ordernote' => $row->ordernote,
                 'totalprice' => $row->totalprice,
                 'id_promotion' => $row->id_promotion,
                 'totalpayment' => $row->totalpayment,
                 'id_courier' => $row->id_courier,
                 'orderdate' => $row->orderdate,
             ];
         }
         return $daily_data;
    }

    public function list_merchantbyuser($id_user){
        $query = $this->db->where('id_user',$id_user)
                            ->get('merchant');
        $merchant_list = NULL;
        foreach ($query->result() as $row)
        {
            $merchant_list[]=[
                'id_merchant' => $row->id_merchant,
                'id_user' => $row->id_user,
                'merchantname' => $row->merchantname,
                'merchantphone' => $row->merchantphone,
                'merchantaddress' => $row->merchantaddress,
                'merchantdesc' => $row->merchantdesc,
                'ktpimage' => $row->ktpimage,
                'isvalidseller' => $row->isvalidseller,
                'merchantcreatedate' =>$row->merchantcreatedate,
                'merchantimage' =>$row->merchantimage
            ];
        }
        return $merchant_list;
    }

    public function sellertrxcount($startdate,$enddate,$status,$id_merchant){
        $select =   array(
            'count(order.id_order) as trx, order.id_order as id_order'
        ); 
        $query = $this->db->where('date(order.orderdate)>=', $startdate)
            ->where('date(order.orderdate)<=', $enddate)
            ->where('order.orderstatus', $status)
            ->where('product.id_merchant',$id_merchant)
            ->select($select)
            ->distinct('detailorder.id_order')
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product','product.id_product = detailorder.id_product') 
            ->group_by('order.id_order')   
            ->get('order');

        $trx_data = NULL;
        $trx_data[]=[
            'trx' => $this->db->affected_rows()
        ];
            
        return $trx_data;
    }

    public function sellertrxcountall($startdate,$enddate,$id_merchant){
        $select =   array(
            'count(order.id_order) as trx, order.id_order as id_order'
        ); 
        $query = $this->db->where('date(order.orderdate)>=', $startdate)
            ->where('date(order.orderdate)<=', $enddate)
            ->where('product.id_merchant',$id_merchant)
            ->select($select)
            ->distinct('detailorder.id_order')
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product','product.id_product = detailorder.id_product') 
            ->group_by('order.id_order')   
            ->get('order');

        $trx_data = NULL;
        $trx_data[]=[
            'trx' => $this->db->affected_rows()
        ];
            
        return $trx_data;
    }

    public function reportcount($startdate,$enddate,$id_merchant){
        $select =   array(
            'order.id_order as id_order, count(order.id_order) as trx'
        ); 
        $query = $this->db->where('date(order.orderdate)>=', $startdate)
            ->where('date(order.orderdate)<=', $enddate)
            ->where('order.orderstatus !=','cart')
            ->where('order.orderstatus !=','pend')
            ->where('order.orderstatus !=','fail')
            ->where('product.id_merchant',$id_merchant)
            ->distinct()
            ->select($select)
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product','product.id_product = detailorder.id_product') 
            ->group_by('order.id_order')   
            ->get('order');

        $trx_data = NULL;
        $trx_data[]=[
            'trx' => $this->db->affected_rows()
        ];
            
        return $trx_data;
    }

    public function reportsum($startdate,$enddate,$id_merchant){
        $select =   array(
            'sum(order.totalpayment) as totalpayment'
        ); 
        $query = $this->db->where('date(order.orderdate)>=', $startdate)
            ->where('date(order.orderdate)<=', $enddate)
            ->where('order.orderstatus !=','cart')
            ->where('order.orderstatus !=','pend')
            ->where('order.orderstatus !=','fail')
            ->where('product.id_merchant',$id_merchant)
            ->select($select)
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product','product.id_product = detailorder.id_product') 
            ->group_by('product.id_merchant')   
            ->get('order');

        $trx_data = NULL;
        foreach ($query->result() as $row)
        {
            $trx_data[]=[
                'totalpayment' => $row->totalpayment
            ];
        }            
        return $trx_data;
    }

    public function sellertrxdata($startdate,$enddate,$status,$id_merchant){

        $query = $this->db->where('date(order.orderdate)>=', $startdate)
            ->where('date(order.orderdate)<=', $enddate)
            ->where('order.orderstatus', $status)
            ->where('product.id_merchant',$id_merchant)
            ->select('order.id_order as id_order, user.username as username, order.totalpayment as totalpayment')
            ->join('user','user.id_user = order.id_user')
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product','product.id_product = detailorder.id_product') 
            ->distinct('detailorder.id_order')
            ->order_by('order.id_order','DESC') 
            ->get('order');

        $order_data = NULL;
        foreach ($query->result() as $row)
        {
            $order_data[]=[
                'id_order' => $row->id_order,
                // 'amount' => $row->amount,
                'username' => $row->username,
                'totalpayment' => $row->totalpayment
            ];
        }
        return $order_data;
    }

    public function sellertrxdataall($startdate,$enddate,$id_merchant){

        $query = $this->db->where('date(order.orderdate)>=', $startdate)
            ->where('date(order.orderdate)<=', $enddate)
            ->where('product.id_merchant',$id_merchant)
            ->select('order.id_order as id_order, user.username as username, order.totalpayment as totalpayment')
            ->join('user','user.id_user = order.id_user')
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product','product.id_product = detailorder.id_product') 
            ->order_by('order.id_order','DESC') 
            ->get('order');

        $order_data = NULL;
        foreach ($query->result() as $row)
        {
            $order_data[]=[
                'id_order' => $row->id_order,
                // 'amount' => $row->amount,
                'username' => $row->username,
                'totalpayment' => $row->totalpayment
            ];
        }
        return $order_data;
    }

    public function actionseller($status,$id_order)
    {
        $query = $this->db->where('id_order',$id_order)
            ->set('orderstatus', $status)
            ->update('order');
        return true;
    }

    public function viewCategory(){
        $query = $this->db->get('category');
        $order_data = NULL;
        foreach ($query->result() as $row) {
            $order_data[]=[
                'id_category' => $row->id_category,
                'category' => $row->category
            ];
        }
        return $order_data;
    }

    public function viewBrand(){
        $query = $this->db->get('brand');
        $order_data = NULL;
        foreach ($query->result() as $row) {
            $order_data[]=[
                'id_brand' => $row->id_brand,
                'brand' => $row->brand,
                'brandimage' => $row->brandimage
            ];
        }
        return $order_data;
    }

    public function categorytopproduct($startdate, $enddate, $id_merchant){
        $select =   array(
            'category.category as cateogry,
            sum(detailorder.amount) as sold
            '
        ); 
        $query = $this->db
            ->distinct()
            ->where('order.orderdate >=',$startdate)
            ->where('order.orderdate <=',$enddate)
            ->where('order.orderstatus !=', 'fail')
            ->where('order.orderstatus !=', 'pend')
            ->where('order.orderstatus !=', 'cart')
            ->where('product.id_merchant',$id_merchant)
            ->select($select)
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product', 'product.id_product = detailorder.id_product')
            ->join('category','category.id_category = product.id_category')
            ->group_by('product.id_category')
            ->order_by('sold','DESC')
            ->limit(5)
            ->get('order');

        $report_data = NULL;
        foreach ($query->result() as $row)
        {
            $report_data[]=[
                'cateogry' =>$row->cateogry,
                'sold' =>$row->sold
            ];
        }
        return $report_data;
    }

    public function brandtopproduct($startdate, $enddate, $id_merchant){
        $select =   array(
            'brand.brand as brand,
            sum(detailorder.amount) as sold
            '
        ); 
        $query = $this->db
            ->distinct()
            ->where('order.orderdate >=',$startdate)
            ->where('order.orderdate <=',$enddate)
            ->where('order.orderstatus !=', 'fail')
            ->where('order.orderstatus !=', 'pend')
            ->where('order.orderstatus !=', 'cart')
            ->where('product.id_merchant',$id_merchant)
            ->select($select)
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product', 'product.id_product = detailorder.id_product')
            ->join('brand','brand.id_brand = product.id_brand')
            ->group_by('product.id_brand')
            ->order_by('sold','DESC')
            ->limit(5)
            ->get('order');

        $report_data = NULL;
        foreach ($query->result() as $row)
        {
            $report_data[]=[
                'brand' =>$row->brand,
                'sold' =>$row->sold
            ];
        }
        return $report_data;
    }

    public function trxdraft($startdate,$enddate,$id_merchant)
    { 
        $query = $this->db
            ->distinct()
            ->where('order.orderdate >=',$startdate)
            ->where('order.orderdate <=',$enddate)
            ->where('order.orderstatus !=', 'fail')
            ->where('order.orderstatus !=', 'pend')
            ->where('order.orderstatus !=', 'cart')
            ->where('product.id_merchant',$id_merchant)
            ->select('order.id_order as id_order, order.orderdate as orderdate, user.username as username, order.totalpayment as totalpayment')
            ->join('user','user.id_user = order.id_user')
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product', 'product.id_product = detailorder.id_product')
            ->order_by('id_order','DESC')
            ->get('order');

        $report_data = NULL;
        foreach ($query->result() as $row)
        {
            $report_data[]=[
                'id_order' =>$row->id_order,
                'orderdate' =>$row->orderdate,
                'username' =>$row->username,
                'totalpayment' =>$row->totalpayment
            ];
        }
        return $report_data;
    }

    public function countproduct($range,$id_merchant)
    { 
        $startdate = date('Y-m-d', strtotime(date('Y-m-d'). ' - '.$range.' days'));
        $enddate = date('Y-m-d');

        $query = $this->db
            ->where('productcreatedate >=',$startdate)
            ->where('productcreatedate <=',$enddate)
            ->where('id_merchant',$id_merchant)
            ->select('count(id_product) as productamount')
            ->get('product');

       
        foreach ($query->result() as $row)
        {

            $report_data=$row->productamount;
        }

        if (empty($report_data)){
            $report_data=0;
        }else{
            $report_data;
        }

        return $report_data;
    }

    public function orderedproduct($range,$id_merchant)
    { 
        $startdate = date('Y-m-d', strtotime(date('Y-m-d'). ' - '.$range.' days'));
        $enddate = date('Y-m-d');

        $query = $this->db
            ->where('order.orderdate >=',$startdate)
            ->where('order.orderdate <=',$enddate)
            ->where('id_merchant',$id_merchant)
            ->where('order.orderstatus !=', 'fail')
            ->where('order.orderstatus !=', 'pend')
            ->where('order.orderstatus !=', 'cart')
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product','product.id_product = detailorder.id_product')
            ->select('sum(detailorder.amount) as orderamount')
            ->group_by('product.id_merchant')
            ->get('order');

        foreach ($query->result() as $row)
        {

            $report_data=$row->orderamount;
        }

        if (empty($report_data)){
            $report_data=0;
        }else{
            $report_data;
        }

        return $report_data;
    }

    public function income($range,$id_merchant)
    { 
        $startdate = date('Y-m-d', strtotime(date('Y-m-d'). ' - '.$range.' days'));
        $enddate = date('Y-m-d');

        $query = $this->db
            ->where('order.orderdate >=',$startdate)
            ->where('order.orderdate <=',$enddate)
            ->where('product.id_merchant',$id_merchant)
            ->where('orderstatus !=', 'fail')
            ->where('orderstatus !=', 'pend')
            ->where('orderstatus !=', 'cart')
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product','product.id_product = detailorder.id_product')
            ->select('sum(order.totalpayment) as income')
            ->group_by('product.id_merchant')
            ->get('order');

        foreach ($query->result() as $row)
        {

            $report_data=$row->income;
        }

        if (empty($report_data)){
            $report_data=0;
        }else{
            $report_data;
        }

        return $report_data;
    }

    public function totalincome($id_merchant)
    { 
        $query = $this->db
            ->where('product.id_merchant',$id_merchant)
            ->where('orderstatus !=', 'fail')
            ->where('orderstatus !=', 'pend')
            ->where('orderstatus !=', 'cart')
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product','product.id_product = detailorder.id_product')
            ->select('sum(order.totalpayment) as totalincome')
            ->group_by('product.id_merchant')
            ->get('order');


        foreach ($query->result() as $row)
        {

            $report_data=$row->totalincome;
        }

        if (empty($report_data)){
            $report_data=0;
        }else{
            $report_data;
        }

        return $report_data;
    }

    public function rating($range,$id_merchant)
    {   $startdate = date('Y-m-d', strtotime(date('Y-m-d'). ' - '.$range.' days'));
        $enddate = date('Y-m-d');

        $query = $this->db
            ->where('order.orderdate >=',$startdate)
            ->where('order.orderdate <=',$enddate)
            ->where('product.id_merchant',$id_merchant)
            ->where('orderstatus !=', 'fail')
            ->where('orderstatus !=', 'pend')
            ->where('orderstatus !=', 'cart')
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product','product.id_product = detailorder.id_product')
            ->select('avg(detailorder.rating) as rating')
            ->group_by('product.id_merchant')
            ->get('order');

        if ((empty($query->result()))){
            $report_data=0;
        }else{
            foreach ($query->result() as $row)
            {

                if(($row->rating)!= NULL){
                    $report_data=$row->rating;
                }else{
                    $report_data=0;
                }
            }
        }
        return $report_data;
    }

    public function uniqbuyer($range,$id_merchant)
    { 
        $startdate = date('Y-m-d', strtotime(date('Y-m-d'). ' - '.$range.' days'));
        $enddate = date('Y-m-d');

        $query = $this->db
            ->distinct('order.id_user')
            ->where('order.orderdate >=',$startdate)
            ->where('order.orderdate <=',$enddate)
            ->where('product.id_merchant',$id_merchant)
            ->where('orderstatus !=', 'fail')
            ->where('orderstatus !=', 'pend')
            ->where('orderstatus !=', 'cart')
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product','product.id_product = detailorder.id_product')
            ->select('count(order.id_user) as uniqbuyer')
            ->group_by('product.id_merchant')
            ->get('order');

        
        foreach ($query->result() as $row)
        {

            $report_data=$row->uniqbuyer;
        }

        if (empty($report_data)){
            $report_data=0;
        }else{
            $report_data;
        }

        return $report_data;
    }

    public function graph_daytrx($dateorder,$id_merchant)
    { 

        $query = $this->db
            ->where('date(order.orderdate)',$dateorder)
            ->where('product.id_merchant',$id_merchant)
            ->where('orderstatus !=', 'fail')
            ->where('orderstatus !=', 'pend')
            ->where('orderstatus !=', 'cart')
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product','product.id_product = detailorder.id_product')
            ->select('sum(order.totalpayment) as total')
            ->group_by('date(order.orderdate)')
            ->get('order');

        $report_data = NULL;
        foreach ($query->result() as $row)
        {

            $report_data[]=[
                'total'=> $row->total
            ];

        }
        return $report_data;
    }

    public function graph_monthtrx($monthorder, $id_merchant)
    { 
        $date = explode('-', $monthorder);
        $year = $date[0];
        $month = $date[1];

        $query = $this->db
            ->where('year(order.orderdate)',$year)
            ->where('month(order.orderdate)',$month)
            ->where('product.id_merchant',$id_merchant)
            ->where('orderstatus !=', 'fail')
            ->where('orderstatus !=', 'pend')
            ->where('orderstatus !=', 'cart')
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product','product.id_product = detailorder.id_product')
            ->select('sum(order.totalpayment) as total')
            ->group_by('month(order.orderdate)')
            ->group_by('year(order.orderdate)')
            ->get('order');

        $report_data = NULL;
        foreach ($query->result() as $row)
        {

            $report_data[]=[
                'total'=> $row->total
            ];

        }
        return $report_data;
    }

    public function graph_yeartrx($monthorder, $id_merchant)
    { 
        $date = explode('-', $monthorder);
        $year = $date[0];
        $month = $date[1];

        $query = $this->db
            ->where('year(order.orderdate)',$year)
            ->where('month(order.orderdate)',$month)
            ->where('product.id_merchant',$id_merchant)
            ->where('orderstatus !=', 'fail')
            ->where('orderstatus !=', 'pend')
            ->where('orderstatus !=', 'cart')
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product','product.id_product = detailorder.id_product')
            ->select('sum(totalpayment) as total')
            ->group_by('month(order.orderdate)')
            ->group_by('year(order.orderdate)')
            ->get('order');

        $report_data = NULL;
        foreach ($query->result() as $row)
        {

            $report_data[]=[
                'total'=> $row->total
            ];

        }
        return $report_data;
    }

    public function graph_daycount($dateorder, $id_merchant)
    { 

        $query = $this->db
            ->where('date(order.orderdate)',$dateorder)
            ->where('product.id_merchant',$id_merchant)
            ->where('orderstatus !=', 'fail')
            ->where('orderstatus !=', 'pend')
            ->where('orderstatus !=', 'cart')
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product','product.id_product = detailorder.id_product')
            ->select('sum(detailorder.amount) as count')
            ->group_by('date(order.orderdate)')
            ->get('order');

        $report_data = NULL;
        foreach ($query->result() as $row)
        {

            $report_data[]=[
                // 'day'=>$row->day,
                'count'=> $row->count
            ];

        }
        return $report_data;
    }

    public function graph_monthcount($monthorder,$id_merchant)
    { 
        $date = explode('-', $monthorder);
        $year = $date[0];
        $month = $date[1];

        $query = $this->db
            ->where('year(order.orderdate)',$year)
            ->where('month(order.orderdate)',$month)
            ->where('product.id_merchant',$id_merchant)
            ->where('orderstatus !=', 'fail')
            ->where('orderstatus !=', 'pend')
            ->where('orderstatus !=', 'cart')
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product','product.id_product = detailorder.id_product')
            ->select('sum(detailorder.amount) as count')
            ->group_by('month(order.orderdate)')
            ->group_by('year(order.orderdate)')
            ->get('order');

        $report_data = NULL;
        foreach ($query->result() as $row)
        {

            $report_data[]=[
                'count'=> $row->count
            ];

        }
        return $report_data;
    }

    public function graph_yearcount($monthorder,$id_merchant)
    { 
        $date = explode('-', $monthorder);
        $year = $date[0];
        $month = $date[1];

        $query = $this->db
            ->where('year(order.orderdate)',$year)
            ->where('month(order.orderdate)',$month)
            ->where('product.id_merchant',$id_merchant)
            ->where('orderstatus !=', 'fail')
            ->where('orderstatus !=', 'pend')
            ->where('orderstatus !=', 'cart')
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product','product.id_product = detailorder.id_product')
            ->select('sum(detailorder.amount) as count')
            ->group_by('month(order.orderdate)')
            ->group_by('year(order.orderdate)')
            ->get('order');

        $report_data = NULL;
        foreach ($query->result() as $row)
        {

            $report_data[]=[
                'count'=> $row->count
            ];

        }
        return $report_data;
    }

    public function topmerchant(){
        $select =   array(
            'count(id_detailorder) as sold, product.id_merchant as id_merchant, merchant.merchantname as merchantname, merchant.merchantimage as merchantimage'
        ); 
        $query = $this->db
            ->select($select)
            ->join('detailorder','detailorder.id_order = order.id_order')
            ->join('product', 'product.id_product = detailorder.id_product')
            ->join('merchant', 'product.id_merchant = merchant.id_merchant')
            ->where('order.orderstatus !=', 'fail')
            ->where('order.orderstatus !=', 'cart')
            ->where('order.orderstatus !=', 'pend')
            ->group_by('product.id_merchant')
            ->order_by('sold','DESC')
            ->limit(5)
            ->get('order');

        $daily_data = NULL;
        foreach ($query->result() as $row)
        {
            if ($row->merchantimage == null){
                $img = null;
            }else{
                $img = "https://sitoleh.com/ktpimage/".$row->merchantimage; 
            }
            $daily_data[]=[
                'sold' => $row->sold,
                'id_merchant' =>$row->id_merchant,
                'merchantname' =>$row->merchantname,
                'merchantimage' =>$img
            ];
        }
        return $daily_data;
    }
}
?>