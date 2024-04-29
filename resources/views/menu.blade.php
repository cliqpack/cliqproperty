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
                            <h1>Menu</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                                <li class="breadcrumb-item active">Menu</li>
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
                                    Add Menu
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <form class="form-horizontal" name="company" id="company">
                                        <div class="mb-3">
                                            <Label for="menu_title" class="form-label">
                                                Menu Title
                                            </Label>
                                            <Input name="menu_title" id="menu_title" type="text"
                                                class="form-control" />

                                        </div>
                                        <div class="mb-3">
                                            <Label for="slug" class="form-label">
                                                Slug
                                            </Label>
                                            <input name="slug" id="slug" type="text" class="form-control" />
                                        </div>
                                        <div class="mb-3">
                                            <Label for="sort_order" class="form-label">
                                                Sort Order
                                            </Label>
                                            <input name="sort_order" id="sort_order" type="text" class="form-control" />
                                        </div>
                                        <div class="mt-3">
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
                                    Menu List
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <table id="example1" class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Menu</th>
                                                <th>Slug</th>
                                                <th>Sort Order</th>
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
         var authUsers = JSON. parse(localStorage.getItem("authUser"));
        $(window).on('load', () => {
            loadCompany();
        })

        function loadCompany() {
            // var url = "http://localhost:8000/api/menus";
            var url = "{{config('app.api_url')}}"+"menus";
            // var url = config('app.menu_url');
            var companyData = "";
           
            console.log(authUsers);
            $.ajax({

                method: "get",
                url: url,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    "Content-Type": "application/json",

                    "Access-Control-Allow-Origin": "*",

                    "Authorization": "Bearer " + authUsers.token,

                },
                success: function(data) {
                    data.menus.forEach((item, key) => {
                        companyData += "<tr>" +
                            "<th scope='row'>" + (key + 1) + "</th>" +
                            "<td>" + item.menu_title + "</td>" +
                            "<td>" + item.slug + "</td>" +
                            "<td>" + item.sort_order + "</td>" +
                            "<td>" +
                            "<button type='button' class='btn btn-block btn-danger' onClick='deleteCompany(" +
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
        $("#add").on("click", addRecord);

        function addRecord() {

            var menu_title = $("#menu_title").val();
            var slug = $("#slug").val();
            var sort_order = $("#sort_order").val();

            

            if (menu_title == "") {
                toastr.error("Enter a menu_title");
                return false;
            }
            if (slug == "") {
                toastr.error("slug cannot be empty");
                return false;
            }
            if (sort_order == "") {
                toastr.error("sort_order cannot be empty");
                return false;
            }


            // get values
            $.ajax({

                method: "post",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    "Authorization": "Bearer " + authUsers.token,
                },
                url: "{{config('app.api_url')}}"+"menus",
                datatype: "html",
                data: $("#company").serialize(),
                success: function(data) {
                    toastr.success('Success');
                    loadCompany();
                    $('#company').trigger("reset");
                },
                error: function(data) {
                    toastr.error(data);
                }
            });
        }

        function deleteCompany(id) {
            // var url = "http://localhost:8000/api/menus/" + id;
            var url = "{{config('app.api_url')}}"+"menus/" + id;
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
    </script>
</body>

</html>
