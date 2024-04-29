<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Header css -->
    @include('includes/head')
    @include('includes/head-table')

</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <!-- Nab content -->
        @include('includes/header')
        <!-- sidebar content -->
        @include('includes/sidebar')

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Role</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                                <li class="breadcrumb-item active">Role</li>
                            </ol>
                        </div>
                    </div>
                </div><!-- /.container-fluid -->
            </section>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-6">
                            <div class="card">
                                <div class="card-header">
                                    Role
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <form class="form-horizontal" name="company" id="company">
                                        <div class="mb-3">
                                            <Label for="roleName" class="form-label">
                                                Role
                                            </Label>
                                            <Input name="roleName" id="roleName" type="text" class="form-control" />

                                        </div>
                                        <div class="mb-3">
                                            <button class="btn btn-primary w-md" type="button" id="add">
                                                Submit
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                <!-- /.card-body -->
                            </div>
                            <!-- /.card -->
                        </div>
                        <div class="col-6">
                            <div class="card">
                                <div class="card-header">
                                    Role List
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <table id="example1" class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Role</th>
                                                <th>Created by</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="comData">

                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <!-- /.card-body -->
                            </div>
                            <!-- /.card -->
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!--modal-content -->
        <div class="modal fade" id="modal-default">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="post" class="form" id="frm_route">
                        <div class="modal-header">
                            <h4 class="modal-title">Assign Module</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="menu_head">Menu</label>
                                <select name="menu" id="menu" class="form-control">
                                    <option value=" ">---select menu-----</option>
                                    @foreach ($menus as $item)
                                        <option value="{{ $item->id }}">{{ $item->menu_title }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" id="role" name="role">
                            </div>

                            <!--end form group -->
                            <div class="form-group">
                                <label for="menu_title">Menu Title</label>
                                <table id="example2" class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Select</th>
                                        </tr>
                                    </thead>
                                    <tbody id="comData2">

                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="addRecord()">Save</button>
                        </div>
                    </form>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <!-- /.modal -->

        <!--modal-content -->
        <div class="modal fade" id="modal-default1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="post" class="form" id="frm_route">
                        <div class="modal-header">
                            <h4 class="modal-title">Delete Menu from Role</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <!--end form group -->
                            <div class="form-group">
                                <label for="menu_title">Menu</label>
                                <table id="example2" class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="comData3">

                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="addRecord()">Save</button>
                        </div>
                    </form>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <!-- /.modal -->

        <!--modal-content -->
        <div class="modal fade" id="modal-default2">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="post" class="form" id="frm_route">
                        <div class="modal-header">
                            <h4 class="modal-title">Role Menu List</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <!--end form group -->
                            <div class="form-group">
                                <label for="menu_title">Menu Title</label>
                                <table id="example2" class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                        </tr>
                                    </thead>
                                    <tbody id="comData4">

                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <!-- /.modal -->

        <!-- Footer content -->
        @include('includes/footer')
        <!-- Footer js -->
        @include('includes/foot')
        @include('includes/foot-table')
        <!-- Bootstrap 4 -->
        <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
        <!-- AdminLTE App -->
        <script src="dist/js/adminlte.js"></script>
        <!-- AdminLTE for demo purposes -->
        <script src="dist/js/demo.js"></script>
    </div>
    <script>
        var authUsers = JSON.parse(localStorage.getItem("authUser"));
        var module = [];
        $("#menu").on('change', () => {
            loadCompany2();
        })

        function loadCompany2() {
           
            // var url = "http://localhost:8000/api/getModules";
            var url = "{{config('app.api_url')}}"+"getModules";
          
    
            
            var companyData = "";
            const formData = {
                menu_id: $("#menu").val(),
            };

            $.ajax({

                method: "post",
                url: url,
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    "Authorization": "Bearer " + authUsers.token,

                },
                success: function(data) {
                    data.forEach((item, key) => {
                        companyData += "<tr>" +
                            "<th scope='row'>" + (key + 1) + "</th>" +
                            "<td>" + item.name + "</td>" +
                            "<td>" +
                            "<input type = 'checkbox' name = 'module[]' value='" + item.id +
                            "' onclick='modulePush(" + item.id + ")'/> " +
                            "</td>" +
                            "</tr>";


                    });
                    if (companyData != "") {
                        $("#comData2").html(companyData);

                        $('#example1').DataTable();
                    }


                }
            });

        }


        function loadCompany3(id) {
            // var url = "http://localhost:8000/api/getRoleModules/"+id;
            var url = "{{config('app.api_url')}}"+"getRoleModules/"+id;
            var companyData = "";
            
            $.ajax({

                method: "get",
                url: url,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    "Authorization": "Bearer " + authUsers.token,

                },
                success: function(data) {
                    var role_id=data.role_id;
                    data.menu.forEach((item, key) => {
                        companyData += "<tr>" +
                            "<th scope='row'>" + (key + 1) + "</th>" +
                            "<td>" + item.menu_title + "</td>" +
                            "<td>" +
                            "<button type='button' class='btn btn-danger w-md' data-toggle='modal'data-target='#modal-default1' onClick='deleteMenu(" +
                            item.id + ","+role_id+")'>Delete</button>" +
                            "</td>" +
                            "</tr>";


                    });
                    if (companyData != "") {
                        $("#comData3").html(companyData);

                        $('#example1').DataTable();
                    }


                }
            });

        }

        function loadCompany4(id) {
            $("#comData4").html('');
            // var url = "http://localhost:8000/api/getRoleModules/"+id;
            var url = "{{config('app.api_url')}}"+"getRoleModules/"+id;
            var companyData = "";
            
            $.ajax({

                method: "get",
                url: url,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    "Authorization": "Bearer " + authUsers.token,

                },
                success: function(data) {
                    var role_id=data.role_id;
                    data.menu.forEach((item, key) => {
                        companyData += "<tr>" +
                            "<th scope='row'>" + (key + 1) + "</th>" +
                            "<td>" + item.menu_title + "</td>" +
                            "</tr>";


                    });
                    if (companyData != "") {
                        $("#comData4").html(companyData);

                        $('#example1').DataTable();
                    }


                }
            });

        }


        $(window).on('load', () => {
            loadCompany();
        })

        function loadCompany() {
            // var url = "http://localhost:8000/api/getAllRoles";
            var url = "{{config('app.api_url')}}"+"getAllRoles";
            var companyData = "";


            $.ajax({

                method: "get",
                url: url,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    "Authorization": "Bearer " + authUsers.token,

                },
                success: function(data) {
                    data.roles.forEach((item, key) => {
                        companyData += "<tr>" +
                            "<th scope='row'>" + (key + 1) + "</th>" +
                            "<td> <a onclick='loadCompany4("+item.id+")' data-toggle='modal' data-target='#modal-default2'>" + item.name + "</a></td>" +
                            "<td>" + item.created_by + "</td>" +
                            "<td>" +
                            "<button type='button' class='btn btn-primary w-md' data-toggle='modal'data-target='#modal-default' onClick='addRoute(" +
                            item.id + ")'>Assign Menu</button>" +
                            "<button type='button' class='btn btn-danger w-md' data-toggle='modal'data-target='#modal-default1' onClick='loadCompany3(" +
                            item.id + ")'>Delete Menu</button>" +
                            "</td>" +
                            "</tr>";


                    });
                    if (companyData != "") {
                        $("#comData").html(companyData);

                        $('#example1').DataTable();
                    }


                }
            });

        }

        function addRoute(id) {
            $("#frm_route").trigger("reset");
            $("#comData2").html('');
            $("#role").val(id);
            module = [];
            loadCompany2(id);
        }

        function modulePush(id) {
            module.push(id.toString());
        }

        $("#add").on("click", addRecord1);
        function addRecord1() {

            // get values
            $.ajax({

                method: "post",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    "Authorization": "Bearer " + authUsers.token,
                },
                // url: "http://localhost:8000/api/roleInsertAjaxRequest",
                url: "{{config('app.api_url')}}"+"roleInsertAjaxRequest",
                datatype: "json",
                data: $("#company").serialize(),
                success: function(data) {
                    toastr.success('Success');
                    loadCompany();
                    $('#company').trigger("reset");
                    $("#modal-default").modal('hide');
                },
                error: function(data) {
                    toastr.error(data);
                }
            });
        }

        $("#add1").on("click", addRecord);

        function addRecord() {

            const formData = {
                "role": $("#role").val(),
                "module": module.toString(),
            };

            // get values
            $.ajax({

                method: "post",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    "Authorization": "Bearer " + authUsers.token,
                },
                // url: "http://localhost:8000/api/roleModuleAssignAjax",
                url: "{{config('app.api_url')}}"+"roleModuleAssignAjax",
                datatype: "json",
                data: formData,
                success: function(data) {
                    toastr.success('Success');
                    loadCompany();
                    $("#modal-default").modal('hide');
                    $("#frm_route").trigger("reset");
                    $("#comData2").html('');
                },
                error: function(data) {
                    toastr.error("Failed");
                }
            });
        }



        function deleteCompany(id) {
            // var url = "http://localhost:8000/api/rolesDeleteAjax";
            var url = "{{config('app.api_url')}}"+"rolesDeleteAjax";
            const formData = {
                'role_id': id
            };
            $.ajax({
                method: "post",
                url: url,
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    "Authorization": "Bearer " + authUsers.token,
                },
                success: function(data) {
                    toastr.success('Menu is Deleted');
                    loadCompany();

                },
                error: function(data) {
                    toastr.error('Menu can not Deleted');
                }
            });
        }

        function deleteMenu(id,role_id) {
            // var url = "http://localhost:8000/api/deleteRoleDetails/"+id+"/"+role_id;
            var url = "{{config('app.api_url')}}"+"deleteRoleDetails/"+id+"/"+role_id;
            
            $.ajax({
                method: "delete",
                url: url,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    "Authorization": "Bearer " + authUsers.token,
                },
                success: function(data) {
                    toastr.success('Menu is Deleted');
                    loadCompany3();

                },
                error: function(data) {
                    toastr.error('Menu can not Deleted');
                }
            });
        }
    </script>
</body>

</html>
