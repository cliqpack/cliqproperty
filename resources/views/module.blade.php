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
                            <h1>Module</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                                <li class="breadcrumb-item active">Module</li>
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
                                    Search Module
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <form class="form-horizontal" name="company" id="company">
                                        <div class="mb-3">
                                            <Label for="menu_title" class="form-label">
                                                Module
                                            </Label>
                                            <select name="menu" id="menu" class="form-control">
                                                <option value=" ">---select menu-----</option>
                                                @foreach ($menus as $item)
                                                    <option value="{{ $item->id }}">{{ $item->menu_title }}</option>
                                                @endforeach
                                            </select>

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
                                    Module List
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <table id="example1" class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Module</th>
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
                            <h4 class="modal-title">Default Modal</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="menu_head">Route</label>
                                <input type="text" class="form-control square" id="route" name="route"
                                    placeholder="Enter menu Head name">
                                <input type="hidden" id="module" name="module">
                            </div>
                            <div class="form-group">
                                <button class="btn btn-primary w-md" type="button" id="add">
                                    Submit
                                </button>
                            </div>
                            <!--end form group -->
                            <div class="form-group">
                                <label for="menu_title">Menu Title</label>
                                <table id="example2" class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Routes</th>
                                            <th>Actions</th>
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
        $("#menu").on('change', () => {
            loadCompany();
        })

        function loadCompany() {
            // var url = "http://localhost:8000/api/getModules";
            var url = "{{config('app.api_url')}}"+"getModules";
            // var url = config('app.module_url');
            var companyData = "";
            const formData = {
                menu_id: $("#menu").val(),
            };

            console.log(authUsers);
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
                            "<button type='button' class='btn btn-primary ml-3' data-toggle='modal' data-target='#modal-default' onClick='addRoute(" +
                            item.id + ")'>Add Route</button>" +
                            "<button type='button' class='btn btn-danger w-md' onClick='deleteCompany(" +
                            item.id + ")'>Delete Route</button>" +
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
            $("#module").val(id);
            loadCompany2(id);
        }

        $("#add").on("click", addRecord);

        function addRecord() {

            var menu_title = $("#route").val();
            var mod = $("#module").val();


            if (menu_title == "") {
                toastr.error("Enter a route");
                return false;
            }


            // get values
            $.ajax({

                method: "post",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    "Authorization": "Bearer " + authUsers.token,
                },
                // url: "http://localhost:8000/api/moduleDetailsInsertAjax",
                url: "{{config('app.api_url')}}"+"moduleDetailsInsertAjax",
                datatype: "html",
                data: $("#frm_route").serialize(),
                success: function(data) {
                    toastr.success('Success');
                    loadCompany2(mod);
                    $('#route').trigger("reset");
                },
                error: function(data) {
                    toastr.error(data);
                }
            });
        }

        function loadCompany2(id) {
            // var url = "http://localhost:8000/api/getRouteByModule";
            var url = "{{config('app.api_url')}}"+"getRouteByModule";
            var companyData = "";
            const formData = {
                id: id,
            };

            console.log(authUsers);
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
                            "<td>" + item.route + "</td>" +
                            "<td>" +
                            "<button type='button' className='btn btn-danger w-md' onClick='deleteCompany2(" +
                            item.id + ")'>Delete Route</button>" +
                            "</td>" +
                            "</tr>";


                    });
                    if (companyData != "") {
                        $("#comData2").html(companyData);

                    }


                }
            });

        }

        function deleteCompany(id) {
            // var url = "http://localhost:8000/api/modules/" + id;
            var url = "{{config('app.api_url')}}"+"getModules/" + id;
            $.ajax({
                method: "delete",
                url: url,
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

        function deleteCompany2(id) {
            // var url = "http://localhost:8000/api/moduleDetailsDeleteAjax/";
            var url = "{{config('app.api_url')}}"+"moduleDetailsDeleteAjax/";
            const formData = {
                'id': id
            };
            var mod = $("#module").val();
            $.ajax({
                method: "post",
                url: url,
                data:formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    "Authorization": "Bearer " + authUsers.token,
                },
                success: function(data) {
                    toastr.success('Menu is Deleted');
                    loadCompany2(mod);

                },
                error: function(data) {
                    toastr.error('Menu can not Deleted');
                }
            });
        }
    </script>
</body>

</html>
