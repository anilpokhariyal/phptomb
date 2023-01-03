 <div class="jumbotron">
        <div class="container">
          <h1 class="display-3">Easy PHP-MySQL Connection</h1>
          <p>Working with Core PHP cost you time and efforts to create and execute queries. PHPTomb is easy to start solution for this problem. No more length code and redundant queries required with phptomb. with inclusion of a single file you can upgrade your core php project to an ORM solution. This project is an opensource project, hence developers can update it and improve it as per their need. Improvement in the existing functionality is appriciated too.</p>
          <p><a class="btn btn-warning btn-lg" href="#" role="button">Download</a></p>
        </div>
      </div>
      <div class="row">
      <?php include('sidebar.php'); ?>
        <div class="col-10">
          <div id="setup">
            <h2>Setting Up</h2>
            <p>To Setup PHPTomb in your core php project just downlaod the PHPTomb and put the tomb folder and server.php file (both present in PHPTomb download) in your project folder.</p>
            <p>Setup server connection on server.php</p>
            <p class="bg-warning p-2">
              $DBNAME = 'database';<br />
              $SERVER = 'localhost';<br />
              $USER = 'username';<br />
              $PASS = 'password';<br />
            </p>
            <p>Include below code at Top of your PHP file you want to connect with mysql.</p>
            <p class="bg-warning p-2">
              require 'tomb/DB.php';
            </p>
            <p>Now you are ready to use PHPTomb.</p>
          </div>
          <div id="useme">
            <h2>Use me</h2>
            <p>PHPTomb use is similart to laravel ORM, however It include only basic functions that comes handy while working with core php projects.</p>
            <p>A basic example of fetching all record from "users" table is below</p>
            <p class="bg-warning p-2">
              DB::table('users')->get();
            </p>
          </div>
          <div id="documentation">
            <h2>Documentation</h2>
            <div id="db">
              <h3>DB</h3>
              <p><strong>DB</strong> is a contant that hold the reference of current connection.</p>
              <p class="bg-warning p-2">
                DB()
              </p>
            </div>
            <div id="create">
              <h3>create()</h3>
              <p><strong>create()</strong> is used when you want to create new table. A table must have column hence it should be followed by addColumn() to add column.
              create can only be use with addColumn() function to add column to the table.</p>
              <p><b>id</b> field will be added as default in table which is "primary key" and "auto increments"</p>
              <p><b>created_at</b> and <b>updated_at</b> will also be added to table of type date by default which will hold the information of record insert datetime and update datetime. </p>
              <p class="bg-warning p-2">
                DB::create($table)
              </p>
            </div>
            <div id="addcolumn">
              <h3>addColumn()</h3>
              <p><strong>addColumn()</strong> is used to addFields in table. addColumn() expects 4 arguments to passed.</p>
              <p>Like: addColumn($fieldname,$fieldtype,$fieldsize,$nullable)</p>
              <p>first argument $fieldname is the name of field. </p>
              <p>$fieldtype is the type of value that will be accepted in field. Like: text,int,varchar etc.</p>
              <p>$fieldsize is the max length value that will be accepted. Like: 10,20,200 etc.</p>
              <p>$nullable expects 'NULL' or 'NOT NULL' as the field value to let nullable or not.</p>
              <p><strong>NOTE: addColumn() will be work only with execute() to make those changes to table.</strong></p>
              <p class="bg-warning p-2">
                DB::create($table)->addColumn('name','varchar',50,'NOT NULL')->addColumn('phone','int',13,'NULL')->execute();
              </p>
            </div>
            <div id="table">
              <h3>table()</h3>
              <p><strong>table()</strong> is used to select the table you want to perform functions on. table() is used with other functional operations.</p>
              <p>Basic Example of table() is below</p>
              <p class="bg-warning p-2">
                DB::table('tablename')->get();
              </p>
            </div>
            <div id="get">
              <h3>get()</h3>
              <p><strong>get()</strong> fetch all records from the selected table. this is the query builder function hence should be called at the end of functions chain.</p>
              <p>Basic Example of get() is below</p>
              <p class="bg-warning p-2">
                DB::table('users')->get();
              </p>
            </div>
            <div id="first">
              <h3>first()</h3>
              <p><strong>first()</strong> fetch first records from the selected table. this is also a query builder function hence should be called at the end of functions chain. you can either perform get() or first().</p>
              <p>Example of first() is below</p>
              <p class="bg-warning p-2">
                DB::table('users')->first();
              </p>
            </div>
            <div id="count">
              <h3>count()</h3>
              <p><strong>count()</strong> counts the records count from the selected table. this is a query builder function hence should be called at the end of functions chain. No Other query builder function can be used with this function.</p>
              <p>Example of count() is below</p>
              <p class="bg-warning p-2">
                DB::table('users')->count();
              </p>
            </div>
            <div id="select">
              <h3>select()</h3>
              <p><strong>select()</strong> will fetch only passed fields of table in output result. It is recommended that you use it right after table() function but not necessarly.</p>
              <p>Example of select() is below</p>
              <p class="bg-warning p-2">
                DB::table('users')->select('column1,column2,column3')->get();
              </p>
            </div>
            <div id="insert">
              <h3>insert()</h3>
              <p><strong>insert()</strong> expects an array parameter with column name as key and value to be inserted.</p>
              <p>Example of insert($array) is below</p>
              <p class="bg-warning p-2">
                DB::table('users')->insert(array('name'=>'Bruno','job'=>'engineer','mobile'=>123456789));
              </p>
            </div>
            <div id="update">
              <h3>update()</h3>
              <p><strong>update()</strong> expects an array parameter with column name as key and value to be updated similar to insert but followed by where() function.</p>
              <p>Example of update($array) is below</p>
              <p class="bg-warning p-2">
                DB::table('users')->where('id',1)->update(array('name'=>'Bruno','job'=>'doctor','mobile'=>9876543210));
              </p>
            </div>
            <div id="where">
              <h3>where()</h3>
              <p><strong>where()</strong> uses to filter record by some conditions on data. where() can be used by three different ways :</p>
              <p>Example of where() with 'key', 'value' parameter</p>
              <p class="bg-warning p-2">
                DB::table('users')->where('key','value')->get();<br />
              LIKE :  DB::table('users')->where('id','45')->get();
              </p>
              <p>Example of where() with 'key','Expression' and 'value' parameter</p>
              <p class="bg-warning p-2">
                DB::table('users')->where('key','Expression','value')->get();<br />
                LIKE : DB::table('users')->where('id','>','4')->get();
              </p>
              <p>Example of where() with multiple conditions</p>
              <p class="bg-warning p-2">
                DB::table('users')->where($array)->get();<br />
                LIKE : DB::table('users')->where(array('name'=>'Bruno','job'=>'doctor'))->get();
              </p>
              <p>
                Possible Expressions : <br />
                <b>=</b> : Equal to<br />
                <b><</b> : less than<br />
                <b><=</b> : less than equal to<br />
                <b>></b> : greater than<br />
                <b>>=</b> : greater than equal to<br />
                <b>LIKE</b> : like value (%value%)<br />
              </p>
            </div>
            <div id="orWhere">
              <h3>orWhere()</h3>
              <p><strong>orWhere()</strong> is usesful for making a query with two alternative conditions like this or that. orWhere() can only use after a where().</p>
              <p>Example of orWhere() is below</p>
              <p class="bg-warning p-2">
                DB::table('users')->where('id',1)->orWhere('name','John')->get();
              </p>
            </div>
            <div id="whereIn">
              <h3>whereIn()</h3>
              <p><strong>whereIn()</strong> is usesful for making a query where a condition could be be true for any of the given value of a column.</p>
              <p>Example of whereIn() is below</p>
              <p class="bg-warning p-2">
                DB::table('users')->whereIn('id',array(1,2,4,7))->get();
              </p>
            </div>
            <div id="whereNotIn">
              <h3>whereNotIn()</h3>
              <p><strong>whereNotIn()</strong> is usesful for making a query where we want all result besides for whom condition is true.</p>
              <p>Example of whereNotIn() is below</p>
              <p class="bg-warning p-2">
                DB::table('users')->whereNotIn('id',array(2,4))->get();<br />
                // this will result in all row but not where id is 2 and 4.
              </p>
            </div>
            <div id="limit">
              <h3>limit()</h3>
              <p><strong>limit()</strong> will retrieve only that number of rows you want from a table. It expects two parameter from and to i.e. from which row to which.</p>
              <p>Example of limit() is below</p>
              <p class="bg-warning p-2">
                DB::table('users')->limit(0,14)->get();<br />
                //this will return only 15 rows from table users.
              </p>
            </div>
            <div id="raw">
              <h3>raw()</h3>
              <p><strong>raw()</strong> is used to pass your custom queries.</p>
              <p>Example of raw() is below</p>
              <p class="bg-warning p-2">
                DB::raw("select *from users where name='tom' and id>9");<br />
              </p>
            </div>
            <div id="leftJoin">
              <h3>leftJoin()</h3>
              <p><strong>leftJoin()</strong> is used fetch records from more than one table with a foreign key. It expects three parameters, second table name, first table primary key and second table foreign key.</p>
              <p>Example of leftJoin($table,$field1,$field2) is below</p>
              <p class="bg-warning p-2">
                DB::table('table2')->leftJoin('table2','table1.id','table2.key')->get();<br />
              </p>
            </div>
            <div id="groupby">
              <h3>groupBy()</h3>
              <p><strong>groupBy()</strong> is used fetch result grouped by a field.</p>
              <p>Example of groupBy() is below</p>
              <p class="bg-warning p-2">
                DB::table('table2')->groupBy('city')->select('city')->get();<br />
              </p>
            </div>
            <div id="oderby">
              <h3>orderBy()</h3>
              <p><strong>orderBy()</strong> is used fetch result with order of certain field.</p>
              <p>orderBy() expects two arguments, first is field name and second order i.e. ASC for Ascending, DESC for Descending.</p>
              <p>Example of orderBy() is below</p>
              <p class="bg-warning p-2">
                DB::table('table2')->orderBy('name','DESC')->select('*')->get();<br />
              </p>
            </div>
          </div>
        </div>
      </div>
    
