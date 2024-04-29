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
                            <h1>Company</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                                <li class="breadcrumb-item active">Company</li>
                            </ol>
                        </div>
                    </div>
                </div><!-- /.container-fluid -->
            </section>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    Company List
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <div class="row mb-4">
                                        <div class="col-2">
                                            <button type="button" class="btn btn-primary" data-toggle='modal'
                                                data-target="#modal-default">Add Company</button>
                                        </div>
                                        <div class="col-10"></div>
                                    </div>
                                    <table id="example1" class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Company</th>
                                                <th>Address</th>
                                                <th>Phone</th>
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
            <!--modal-content -->
            <div class="modal fade" id="modal-default">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Add Company</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="company_frm" name="company_frm" class="form-horizontal">
                                <div class="mb-3"><label for="company" class="form-label form-label">Company</label>
                                    <input name="company_name" type="text" class="form-control" />
                                </div>
                                {{-- <div class="mb-3"><label for="address" class="form-label form-label">Address</label>
                                    <input name="address" type="text" class="form-control" />
                                </div> --}}
                                <div class="mb-3"><label for="region" class="form-label form-label">Region</label>
                                    <input name="region" type="text" class="form-control" />
                                </div>
                                <div class="mb-3"><label for="address" class="form-label form-label">Address</label>
                                    <input name="address" type="text" class="form-control" />
                                </div>
                                <div class="mb-3"><label for="country" class="form-label form-label">Country</label>
                                    <input name="country" type="text" class="form-control" />
                                </div>
                                <div class="mb-3"><label for="Licence_number" class="form-label form-label">Licence Number</label>
                                    <input name="licence_number" type="text" class="form-control" />
                                </div>
                                <div class="mb-3"><label for="phone" class="form-label form-label">Phone</label>
                                    <input name="phone" type="text" class="form-control" />
                                </div>
                                <div class="mb-6">
                                    <label class="block">
                                        <span class="">Include property key number on work order</span>
                                        <input type="checkbox" name="include_property_key_number"  />
                                    </label>
                                    
                                </div>
                                <div class="mb-6">
                                    <label class="block">
                                        <span class="">Update inspection date on tenant move-in</span>
                                        <input type="checkbox" name="update_inspection_date"  />
                                    </label>
                                    
                                </div>
                                <div class="mb-6">
                                    <label class="block">
                                        <span class="">Client Access</span>
                                        <input type="checkbox" name="client_access"  />
                                    </label>
                                    
                                </div>
                                <div class="mb-3"><label for="client_access_url" class="form-label form-label">Client access URL</label>
                                    <input name="client_access_url" type="text" class="form-control" />
                                </div>
                                <div class="mb-6">
                                    <label class="block">
                                        <span class="">Rental position On Receipts</span>
                                        <input type="checkbox" name="rental_position_on_receipts"  />
                                    </label>
                                    
                                </div>
                                <div class="mb-6">
                                    <label class="block">
                                        <span class="">Show Effective Paid To Dates</span>
                                        <input type="checkbox" name="show_effective_paid_to_dates"  />
                                    </label>
                                    
                                </div>
                                
                                <div class="mb-6">
                                    <label class="block">
                                        <span class="">Include paid bills when printing Owner Statements</span>
                                        <input type="checkbox" name="include_paid_bills"  />
                                    </label>
                                    
                                </div>
                                
                                <div class="mb-6">
                                    <label class="block">
                                        <span class="">Bill approval</span>
                                        <input type="checkbox" name="bill_approval"  />
                                    </label>
                                    
                                </div>
                                <div class="mb-6">
                                    <label class="block">
                                        <span class="">Join the test program</span>
                                        <input type="checkbox" name="join_the_test_program"  />
                                    </label>
                                    
                                </div>
                                
                                <div class="mb-3">
                                    <button type="button" class="btn btn-primary" onclick="addRecord1()">Save</button>
                                </div>
                            </form>

                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>
            <!-- /.modal -->
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
        var authUsers = JSON.parse(localStorage.getItem("authUser"));
        $(window).on('load', () => {
            loadCompany();
        })

        function loadCompany() {
            // var url = "http://localhost:8000/api/companies";

            var url = "{{ config('app.api_url') }}" + "companies";
            var companyData = "";
            $.ajax({

                method: "get",
                url: url,
                success: function(data) {
                    data.data.companies.forEach((item, key) => {
                        companyData += "<tr>" +
                            "<th scope='row'>" + (key + 1) + "</th>" +
                            "<td>" + item.company_name + "</td>" +
                            "<td>" + item.address + "</td>" +
                            "<td>" + item.phone + "</td>" +
                            "<td>" +
                            "<button type='button' class='btn btn-danger w-md' onClick='deleteCompany(" +
                            item.id + ")'>Delete</button>" +
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

        function addRecord1() {

            $.ajax({

                method: "post",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    "Authorization": "Bearer " + authUsers.token,
                },
                // url: "http://localhost:8000/companiesAdd",
                url: "{{ config('app.api_url') }}" + "companies",
                datatype: "html",
                data: $("#company_frm").serialize(),
                success: function(data) {
                    toastr.success('Success');
                    $("#modal-default").modal('hide');
                    loadCompany();
                },
                error: function(data) {
                    toastr.error(data);
                }
            });
        }

        function deleteCompany(id) {
            // var url = "http://localhost:8000/api/companies/" + id;

            var url = "{{ config('app.api_url') }}" + "companies/" + id;
            $.ajax({
                method: "delete",
                url: url,
                success: function(data) {
                    toastr.success('Company is Deleted');
                    loadCompany();

                },
                error: function(data) {
                    toastr.error('Company can not Deleted');
                }
            });
        }
    </script>
</body>

</html>
