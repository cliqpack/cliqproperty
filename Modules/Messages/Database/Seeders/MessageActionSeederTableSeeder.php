<?php

namespace Modules\Messages\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Messages\Entities\MessageAction;
use Modules\Messages\Entities\MergeField;
use Modules\Messages\Entities\MergeSubfield;

class MessageActionSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Message Actions
        $messageActions = [
            'Contact' => [
                'mergeFields' => [
                    'Date' => ['Date Today'],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],
            'Inspections All' => [
                'mergeFields' => [
                    'Inspection' => [
                        'Inspection Date',
                        'Inspection Time',
                        'Inspection Duration',
                        'Inspection Summary',
                        'Inspection Description',
                        'Inspection Two Hour Window',
                        'Inspection Tenant Return By',
                        'Inspection Link Entry'
                    ],
                    'Form' => ['Form Link'],
                    'Date' => ['Date Today'],
                    'Property' => ['Property Single Line', 'Property Multi Line', 'Property Key Number'],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Owner' => [
                        'Owner Abn',
                        'Owner Company Name',
                        'Owner Website',
                        'Owner Physical Address',
                        'Owner Postal Address',
                        'Owner Email',
                        'Owner Phone Numbers',
                        'Owner First Name',
                        'Owner Last Name',
                        'Owner Salutation',
                        'Owner All Names',
                        'Owner Address Block'
                    ],
                    'Tenant' => [
                        'Tenant Abn',
                        'Tenant Company Name',
                        'Tenant Website',
                        'Tenant Physical Address',
                        'Tenant Postal Address',
                        'Tenant Email',
                        'Tenant Phone Numbers',
                        'Tenant First Name',
                        'Tenant Last Name',
                        'Tenant Salutation',
                        'Tenant All Names',
                        'Tenant Address Block',
                        'Tenant Bank Reference'
                    ],
                    'Property Manager' => [
                        'Property Manager First Name',
                        'Property Manager Last Name',
                        'Property Manager Job Title',
                        'Property Manager Email',
                        'Property Manager Work Phone',
                        'Property Manager Mobile Phone'
                    ],
                    'Assigned User' => [
                        'Assigned User First Name',
                        'Assigned User Last Name',
                        'Assigned User Job Title',
                        'Assigned User Email',
                        'Assigned User Work Phone',
                        'Assigned User Mobile Phone'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],
            'Inspections Routine' => [
                'mergeFields' => [
                    'Inspection' => [
                        'Inspection Date',
                        'Inspection Time',
                        'Inspection Duration',
                        'Inspection Summary',
                        'Inspection Description',
                        'Inspection Two Hour Window',
                        'Inspection Tenant Return By',
                        'Inspection Link Entry'
                    ],
                    'Form' => ['Form Link'],
                    'Date' => ['Date Today'],
                    'Property' => ['Property Single Line', 'Property Multi Line', 'Property Key Number'],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Owner' => [
                        'Owner Abn',
                        'Owner Company Name',
                        'Owner Website',
                        'Owner Physical Address',
                        'Owner Postal Address',
                        'Owner Email',
                        'Owner Phone Numbers',
                        'Owner First Name',
                        'Owner Last Name',
                        'Owner Salutation',
                        'Owner All Names',
                        'Owner Address Block'
                    ],
                    'Tenant' => [
                        'Tenant Abn',
                        'Tenant Company Name',
                        'Tenant Website',
                        'Tenant Physical Address',
                        'Tenant Postal Address',
                        'Tenant Email',
                        'Tenant Phone Numbers',
                        'Tenant First Name',
                        'Tenant Last Name',
                        'Tenant Salutation',
                        'Tenant All Names',
                        'Tenant Address Block',
                        'Tenant Bank Reference'
                    ],
                    'Property Manager' => [
                        'Property Manager First Name',
                        'Property Manager Last Name',
                        'Property Manager Job Title',
                        'Property Manager Email',
                        'Property Manager Work Phone',
                        'Property Manager Mobile Phone'
                    ],
                    'Assigned User' => [
                        'Assigned User First Name',
                        'Assigned User Last Name',
                        'Assigned User Job Title',
                        'Assigned User Email',
                        'Assigned User Work Phone',
                        'Assigned User Mobile Phone'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],
            'Job' => [
                'mergeFields' => [
                    'Job' => [
                        'Job Summary',
                        'Job Access',
                        'Job Number',
                        'Job Due Date',
                        'Job Access Name',
                        'Job Access Phone',
                        'Job Quote Reference',
                        'Job Quote Amount',
                        'Job Quoted On',
                        'Job Quote',
                        'Job Work Order',
                        'Job Description',
                        'Job Access Contact Block'
                    ],
                    'Date' => ['Date Today'],
                    'Property' => ['Property Single Line', 'Property Multi Line', 'Property Key Number'],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Owner' => [
                        'Owner Abn',
                        'Owner Company Name',
                        'Owner Website',
                        'Owner Physical Address',
                        'Owner Postal Address',
                        'Owner Email',
                        'Owner Phone Numbers',
                        'Owner First Name',
                        'Owner Last Name',
                        'Owner Salutation',
                        'Owner All Names',
                        'Owner Address Block'
                    ],
                    'Tenant' => [
                        'Tenant Abn',
                        'Tenant Company Name',
                        'Tenant Website',
                        'Tenant Physical Address',
                        'Tenant Postal Address',
                        'Tenant Email',
                        'Tenant Phone Numbers',
                        'Tenant First Name',
                        'Tenant Last Name',
                        'Tenant Salutation',
                        'Tenant All Names',
                        'Tenant Address Block',
                        'Tenant Bank Reference'
                    ],
                    'Supplier' => [
                        'Supplier Abn',
                        'Supplier Company Name',
                        'Supplier Website',
                        'Supplier Physical Address',
                        'Supplier Postal Address',
                        'Supplier Email',
                        'Supplier Phone Numbers',
                        'Supplier First Name',
                        'Supplier Last Name',
                        'Supplier Salutation',
                        'Supplier All Names',
                        'Supplier Address Block'
                    ],
                    'Property Manager' => [
                        'Property Manager First Name',
                        'Property Manager Last Name',
                        'Property Manager Job Title',
                        'Property Manager Email',
                        'Property Manager Work Phone',
                        'Property Manager Mobile Phone'
                    ],
                    'Assigned User' => [
                        'Assigned User First Name',
                        'Assigned User Last Name',
                        'Assigned User Job Title',
                        'Assigned User Email',
                        'Assigned User Work Phone',
                        'Assigned User Mobile Phone'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],
            'Key Management' => [
                'mergeFields' => [
                    'Key' => [
                        'Key Checked Out Date',
                        'Key Checked Out Time',
                        'Key Return Due Date',
                        'Key Return Due Time',
                        'Key Days Overdue',
                        'Key Number'
                    ],
                    'Date' => ['Date Today'],
                    'Property' => ['Property Single Line', 'Property Multi Line', 'Property Key Number'],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Property Manager' => [
                        'Property Manager First Name',
                        'Property Manager Last Name',
                        'Property Manager Job Title',
                        'Property Manager Email',
                        'Property Manager Work Phone',
                        'Property Manager Mobile Phone'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],
            'Lease Renewal' => [
                'mergeFields' => [
                    'Lease Renewal' => [
                        'Lease Renewal Proposed Agreement Start',
                        'Lease Renewal Proposed Agreement End',
                        'Lease Renewal Proposed Period Length',
                        'Lease Renewal Proposed New Rent Amount',
                        'Lease Renewal Proposed Comments'
                    ],
                    'Tenant' => [
                        'Tenant Paid To Date',
                        'Tenant Part Paid Amount',
                        'Tenant Effective Paid To Date',
                        'Tenant Total Arrears Amount',
                        'Tenant Total Arrears Amount By Period',
                        'Tenant Rent Arrears Days',
                        'Tenant Rent Arrears Amount',
                        'Tenant Rent Arrears Amount By Period',
                        'Tenant Rent Owing To Vacate',
                        'Tenant Invoice Arrears Amount',
                        'Tenant Invoice Arrears Days',
                        'Tenant Rent Increase Date',
                        'Tenant Rent Increase Amount',
                        'Tenant Bond Reference',
                        'Tenant Bond Arrears Amount',
                        'Tenant Bond Receipted Amount',
                        'Tenant Bond Required Amount',
                        'Tenant Bond Already Paid Amount',
                        'Tenant Bond Held Amount',
                        'Tenant Lease Term',
                        'Tenant Rent Amount',
                        'Tenant Move In',
                        'Tenant Move Out',
                        'Tenant Agreement Start',
                        'Tenant Agreement End',
                        'Tenant Bank Reference',
                        'Tenant Rental Period',
                        'Tenant Break Lease',
                        'Tenant Termination',
                        'Tenant Abn',
                        'Tenant Company Name',
                        'Tenant Website',
                        'Tenant Physical Address',
                        'Tenant Postal Address',
                        'Tenant Email',
                        'Tenant Phone Numbers',
                        'Tenant First Name',
                        'Tenant Last Name',
                        'Tenant Salutation',
                        'Tenant All Names',
                        'Tenant Address Block'
                    ],
                    'Date' => ['Date Today'],
                    'Property' => ['Property Single Line', 'Property Multi Line', 'Property Key Number'],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Owner' => [
                        'Owner Abn',
                        'Owner Company Name',
                        'Owner Website',
                        'Owner Physical Address',
                        'Owner Postal Address',
                        'Owner Email',
                        'Owner Phone Numbers',
                        'Owner First Name',
                        'Owner Last Name',
                        'Owner Salutation',
                        'Owner All Names',
                        'Owner Address Block'
                    ],
                    'Property Manager' => [
                        'Property Manager First Name',
                        'Property Manager Last Name',
                        'Property Manager Job Title',
                        'Property Manager Email',
                        'Property Manager Work Phone',
                        'Property Manager Mobile Phone'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],
            'Messages' => [
                'mergeFields' => [
                    'Date' => ['Date Today'],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Property Manager' => [
                        'Property Manager First Name',
                        'Property Manager Last Name',
                        'Property Manager Job Title',
                        'Property Manager Email',
                        'Property Manager Work Phone',
                        'Property Manager Mobile Phone'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],
            'Owner Contact' => [
                'mergeFields' => [
                    'Date' => ['Date Today'],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Owner' => [
                        'Owner Abn',
                        'Owner Company Name',
                        'Owner Website',
                        'Owner Physical Address',
                        'Owner Postal Address',
                        'Owner Email',
                        'Owner Phone Numbers',
                        'Owner First Name',
                        'Owner Last Name',
                        'Owner Salutation',
                        'Owner All Names',
                        'Owner Address Block'
                    ],
                    'Property Manager' => [
                        'Property Manager First Name',
                        'Property Manager Last Name',
                        'Property Manager Job Title',
                        'Property Manager Email',
                        'Property Manager Work Phone',
                        'Property Manager Mobile Phone'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],
            'Reminder' => [
                'mergeFields' => [
                    'Reminder' => [
                        'Reminder Pdf',
                        'Reminder Name',
                        'Reminder Notes',
                        'Reminder Due Date',
                        'Reminder Certificate Expiry Date',
                        'Reminder Frequency'
                    ],
                    'Date' => ['Date Today'],
                    'Property' => [
                        'Property Single Line',
                        'Property Multi Line',
                        'Property Key Number'
                    ],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Owner' => [
                        'Owner Abn',
                        'Owner Company Name',
                        'Owner Website',
                        'Owner Physical Address',
                        'Owner Postal Address',
                        'Owner Email',
                        'Owner Phone Numbers',
                        'Owner First Name',
                        'Owner Last Name',
                        'Owner Salutation',
                        'Owner All Names',
                        'Owner Address Block'
                    ],
                    'Tenant' => [
                        'Tenant Abn',
                        'Tenant Company Name',
                        'Tenant Website',
                        'Tenant Physical Address',
                        'Tenant Postal Address',
                        'Tenant Email',
                        'Tenant Phone Numbers',
                        'Tenant First Name',
                        'Tenant Last Name',
                        'Tenant Salutation',
                        'Tenant All Names',
                        'Tenant Address Block',
                        'Tenant Bank Reference'
                    ],
                    'Supplier' => [
                        'Supplier Abn',
                        'Supplier Company Name',
                        'Supplier Website',
                        'Supplier Physical Address',
                        'Supplier Postal Address',
                        'Supplier Email',
                        'Supplier Phone Numbers',
                        'Supplier First Name',
                        'Supplier Last Name',
                        'Supplier Salutation',
                        'Supplier All Names',
                        'Supplier Address Block'
                    ],
                    'Property Manager' => [
                        'Property Manager First Name',
                        'Property Manager Last Name',
                        'Property Manager Job Title',
                        'Property Manager Email',
                        'Property Manager Work Phone',
                        'Property Manager Mobile Phone'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],
            'Rental Listing' => [
                'mergeFields' => [
                    'Listing' => [
                        'Listing Headline',
                        'Listing Status',
                        'Listing Publish Date',
                        'Listing Update Date',
                        'Listing Providers',
                        'Listing Description',
                        'Listing Inspection Times',
                        'Listing Agent Primary Name',
                        'Listing Agent Primary Phone Numbers',
                        'Listing Agent Second Name',
                        'Listing Agent Second Phone Numbers',
                        'Listing Rent Amount',
                        'Listing Rent Period',
                        'Listing Available Date',
                        'Listing Bond Amount'
                    ],
                    'Date' => ['Date Today'],
                    'Property' => [
                        'Property Single Line',
                        'Property Multi Line',
                        'Property Key Number'
                    ],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Owner' => [
                        'Owner Abn',
                        'Owner Company Name',
                        'Owner Website',
                        'Owner Physical Address',
                        'Owner Postal Address',
                        'Owner Email',
                        'Owner Phone Numbers',
                        'Owner First Name',
                        'Owner Last Name',
                        'Owner Salutation',
                        'Owner All Names',
                        'Owner Address Block'
                    ],
                    'Tenant' => [
                        'Tenant Abn',
                        'Tenant Company Name',
                        'Tenant Website',
                        'Tenant Physical Address',
                        'Tenant Postal Address',
                        'Tenant Email',
                        'Tenant Phone Numbers',
                        'Tenant First Name',
                        'Tenant Last Name',
                        'Tenant Salutation',
                        'Tenant All Names',
                        'Tenant Address Block',
                        'Tenant Bank Reference'
                    ],
                    'Assigned User' => [
                        'Assigned User First Name',
                        'Assigned User Last Name',
                        'Assigned User Job Title',
                        'Assigned User Email',
                        'Assigned User Work Phone',
                        'Assigned User Mobile Phone'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],
            'Sale Listing' => [
                'mergeFields' => [
                    'Listing' => [
                        'Listing Headline',
                        'Listing Status',
                        'Listing Publish Date',
                        'Listing Update Date',
                        'Listing Providers',
                        'Listing Description',
                        'Listing Inspection Times',
                        'Listing Agent Primary Name',
                        'Listing Agent Primary Phone Numbers',
                        'Listing Agent Second Name',
                        'Listing Agent Second Phone Numbers',
                        'Listing Rent Amount',
                        'Listing Rent Period',
                        'Listing Available Date',
                        'Listing Bond Amount'
                    ],
                    'Date' => ['Date Today'],
                    'Property' => [
                        'Property Single Line',
                        'Property Multi Line',
                        'Property Key Number'
                    ],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Owner' => [
                        'Owner Abn',
                        'Owner Company Name',
                        'Owner Website',
                        'Owner Physical Address',
                        'Owner Postal Address',
                        'Owner Email',
                        'Owner Phone Numbers',
                        'Owner First Name',
                        'Owner Last Name',
                        'Owner Salutation',
                        'Owner All Names',
                        'Owner Address Block'
                    ],
                    'Tenant' => [
                        'Tenant Abn',
                        'Tenant Company Name',
                        'Tenant Website',
                        'Tenant Physical Address',
                        'Tenant Postal Address',
                        'Tenant Email',
                        'Tenant Phone Numbers',
                        'Tenant First Name',
                        'Tenant Last Name',
                        'Tenant Salutation',
                        'Tenant All Names',
                        'Tenant Address Block',
                        'Tenant Bank Reference'
                    ],
                    'Assigned User' => [
                        'Assigned User First Name',
                        'Assigned User Last Name',
                        'Assigned User Job Title',
                        'Assigned User Email',
                        'Assigned User Work Phone',
                        'Assigned User Mobile Phone'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],
            'Sales Agreement' => [
                'mergeFields' => [
                    'Sales' => [
                        'Sales Agreement Folio Code',
                        'Sales Agreement Status',
                        'Sales Agreement Seller',
                        'Sales Agreement Seller Phone',
                        'Sales Agreement Seller Email',
                        'Sales Agreement Asking Price',
                        'Sales Agreement Purchase Price',
                        'Sales Agreement Commission',
                        'Sales Agreement Buyer',
                        'Sales Agreement Buyer Phone',
                        'Sales Agreement Buyer Email',
                        'Sales Agreement Agreement Start Date',
                        'Sales Agreement Agreement End Date',
                        'Sales Agreement Deposit Due',
                        'Sales Agreement Contract Exchange',
                        'Sales Agreement Settlement'
                    ],
                    'Date' => ['Date Today'],
                    'Property' => [
                        'Property Single Line',
                        'Property Multi Line',
                        'Property Key Number'
                    ],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Owner' => [
                        'Owner Abn',
                        'Owner Company Name',
                        'Owner Website',
                        'Owner Physical Address',
                        'Owner Postal Address',
                        'Owner Email',
                        'Owner Phone Numbers',
                        'Owner First Name',
                        'Owner Last Name',
                        'Owner Salutation',
                        'Owner All Names',
                        'Owner Address Block'
                    ],
                    'Tenant' => [
                        'Tenant Abn',
                        'Tenant Company Name',
                        'Tenant Website',
                        'Tenant Physical Address',
                        'Tenant Postal Address',
                        'Tenant Email',
                        'Tenant Phone Numbers',
                        'Tenant First Name',
                        'Tenant Last Name',
                        'Tenant Salutation',
                        'Tenant All Names',
                        'Tenant Address Block',
                        'Tenant Bank Reference'
                    ],
                    'Property Manager' => [
                        'Property Manager First Name',
                        'Property Manager Last Name',
                        'Property Manager Job Title',
                        'Property Manager Email',
                        'Property Manager Work Phone',
                        'Property Manager Mobile Phone'
                    ],
                    'Assigned User' => [
                        'Assigned User First Name',
                        'Assigned User Last Name',
                        'Assigned User Job Title',
                        'Assigned User Email',
                        'Assigned User Work Phone',
                        'Assigned User Mobile Phone'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],
            'Task' => [
                'mergeFields' => [
                    'Tenant' => [
                        'Tenant Paid To Date',
                        'Tenant Part Paid Amount',
                        'Tenant Effective Paid To Date',
                        'Tenant Total Arrears Amount',
                        'Tenant Total Arrears Amount By Period',
                        'Tenant Rent Arrears Days',
                        'Tenant Rent Arrears Amount',
                        'Tenant Rent Arrears Amount By Period',
                        'Tenant Rent Owing To Vacate',
                        'Tenant Invoice Arrears Amount',
                        'Tenant Invoice Arrears Days',
                        'Tenant Rent Increase Date',
                        'Tenant Rent Increase Amount',
                        'Tenant Bond Reference',
                        'Tenant Bond Arrears Amount',
                        'Tenant Bond Receipted Amount',
                        'Tenant Bond Required Amount',
                        'Tenant Bond Already Paid Amount',
                        'Tenant Bond Held Amount',
                        'Tenant Lease Term',
                        'Tenant Rent Amount',
                        'Tenant Move In',
                        'Tenant Move Out',
                        'Tenant Agreement Start',
                        'Tenant Agreement End',
                        'Tenant Bank Reference',
                        'Tenant Rental Period',
                        'Tenant Break Lease',
                        'Tenant Termination',
                        'Tenant Abn',
                        'Tenant Company Name',
                        'Tenant Website',
                        'Tenant Physical Address',
                        'Tenant Postal Address',
                        'Tenant Email',
                        'Tenant Phone Numbers',
                        'Tenant First Name',
                        'Tenant Last Name',
                        'Tenant Salutation',
                        'Tenant All Names',
                        'Tenant Address Block'
                    ],
                    'Date' => ['Date Today'],
                    'Property' => [
                        'Property Single Line',
                        'Property Multi Line',
                        'Property Key Number'
                    ],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Owner' => [
                        'Owner Abn',
                        'Owner Company Name',
                        'Owner Website',
                        'Owner Physical Address',
                        'Owner Postal Address',
                        'Owner Email',
                        'Owner Phone Numbers',
                        'Owner First Name',
                        'Owner Last Name',
                        'Owner Salutation',
                        'Owner All Names',
                        'Owner Address Block'
                    ],
                    'Property Manager' => [
                        'Property Manager First Name',
                        'Property Manager Last Name',
                        'Property Manager Job Title',
                        'Property Manager Email',
                        'Property Manager Work Phone',
                        'Property Manager Mobile Phone'
                    ],
                    'Assigned User' => [
                        'Assigned User First Name',
                        'Assigned User Last Name',
                        'Assigned User Job Title',
                        'Assigned User Email',
                        'Assigned User Work Phone',
                        'Assigned User Mobile Phone'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],
            'Tenancy' => [
                'mergeFields' => [
                    'Tenant' => [
                        'Tenant Paid To Date',
                        'Tenant Part Paid Amount',
                        'Tenant Effective Paid To Date',
                        'Tenant Total Arrears Amount',
                        'Tenant Total Arrears Amount By Period',
                        'Tenant Rent Arrears Days',
                        'Tenant Rent Arrears Amount',
                        'Tenant Rent Arrears Amount By Period',
                        'Tenant Rent Owing To Vacate',
                        'Tenant Invoice Arrears Amount',
                        'Tenant Invoice Arrears Days',
                        'Tenant Rent Increase Date',
                        'Tenant Rent Increase Amount',
                        'Tenant Bond Reference',
                        'Tenant Bond Arrears Amount',
                        'Tenant Bond Receipted Amount',
                        'Tenant Bond Required Amount',
                        'Tenant Bond Already Paid Amount',
                        'Tenant Bond Held Amount',
                        'Tenant Lease Term',
                        'Tenant Rent Amount',
                        'Tenant Move In',
                        'Tenant Move Out',
                        'Tenant Agreement Start',
                        'Tenant Agreement End',
                        'Tenant Bank Reference',
                        'Tenant Rental Period',
                        'Tenant Break Lease',
                        'Tenant Termination',
                        'Tenant Abn',
                        'Tenant Company Name',
                        'Tenant Website',
                        'Tenant Physical Address',
                        'Tenant Postal Address',
                        'Tenant Email',
                        'Tenant Phone Numbers',
                        'Tenant First Name',
                        'Tenant Last Name',
                        'Tenant Salutation',
                        'Tenant All Names',
                        'Tenant Address Block'
                    ],
                    'Date' => ['Date Today'],
                    'Property' => [
                        'Property Single Line',
                        'Property Multi Line',
                        'Property Key Number'
                    ],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Owner' => [
                        'Owner Abn',
                        'Owner Company Name',
                        'Owner Website',
                        'Owner Physical Address',
                        'Owner Postal Address',
                        'Owner Email',
                        'Owner Phone Numbers',
                        'Owner First Name',
                        'Owner Last Name',
                        'Owner Salutation',
                        'Owner All Names',
                        'Owner Address Block'
                    ],
                    'Property Manager' => [
                        'Property Manager First Name',
                        'Property Manager Last Name',
                        'Property Manager Job Title',
                        'Property Manager Email',
                        'Property Manager Work Phone',
                        'Property Manager Mobile Phone'
                    ],
                    'Assigned User' => [
                        'Assigned User First Name',
                        'Assigned User Last Name',
                        'Assigned User Job Title',
                        'Assigned User Email',
                        'Assigned User Work Phone',
                        'Assigned User Mobile Phone'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],
            'Tenant Invoice' => [
                'mergeFields' => [
                    'Invoice' => [
                        'Invoice Due Date',
                        'Invoice Balance Due',
                        'Invoice Link',
                        'Invoice Document Link'
                    ],
                    'Date' => ['Date Today'],
                    'Property' => [
                        'Property Single Line',
                        'Property Multi Line',
                        'Property Key Number'
                    ],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Tenant' => [
                        'Tenant Abn',
                        'Tenant Company Name',
                        'Tenant Website',
                        'Tenant Physical Address',
                        'Tenant Postal Address',
                        'Tenant Email',
                        'Tenant Phone Numbers',
                        'Tenant First Name',
                        'Tenant Last Name',
                        'Tenant Salutation',
                        'Tenant All Names',
                        'Tenant Address Block',
                        'Tenant Bank Reference'
                    ],
                    'Property Manager' => [
                        'Property Manager First Name',
                        'Property Manager Last Name',
                        'Property Manager Job Title',
                        'Property Manager Email',
                        'Property Manager Work Phone',
                        'Property Manager Mobile Phone'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],
            'Tenant Rent Invoice' => [
                'mergeFields' => [
                    'Invoice' => [
                        'Invoice Due Date',
                        'Invoice Balance Due',
                        'Invoice Link',
                        'Invoice Document Link'
                    ],
                    'Date' => ['Date Today'],
                    'Property' => [
                        'Property Single Line',
                        'Property Multi Line',
                        'Property Key Number'
                    ],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Tenant' => [
                        'Tenant Abn',
                        'Tenant Company Name',
                        'Tenant Website',
                        'Tenant Physical Address',
                        'Tenant Postal Address',
                        'Tenant Email',
                        'Tenant Phone Numbers',
                        'Tenant First Name',
                        'Tenant Last Name',
                        'Tenant Salutation',
                        'Tenant All Names',
                        'Tenant Address Block',
                        'Tenant Bank Reference'
                    ],
                    'Property Manager' => [
                        'Property Manager First Name',
                        'Property Manager Last Name',
                        'Property Manager Job Title',
                        'Property Manager Email',
                        'Property Manager Work Phone',
                        'Property Manager Mobile Phone'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],
            'Tenant Receipt' => [
                'mergeFields' => [
                    'Receipt' => [
                        'Receipt Number',
                        'Receipt Issue Date',
                        'Receipt Gross Amount',
                        'Receipt Link',
                        'Receipt Effective Paid To Date'
                    ],
                    'Date' => ['Date Today'],
                    'Property' => [
                        'Property Single Line',
                        'Property Multi Line',
                        'Property Key Number'
                    ],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Tenant' => [
                        'Tenant Abn',
                        'Tenant Company Name',
                        'Tenant Website',
                        'Tenant Physical Address',
                        'Tenant Postal Address',
                        'Tenant Email',
                        'Tenant Phone Numbers',
                        'Tenant First Name',
                        'Tenant Last Name',
                        'Tenant Salutation',
                        'Tenant All Names',
                        'Tenant Address Block',
                        'Tenant Bank Reference'
                    ],
                    'Property Manager' => [
                        'Property Manager First Name',
                        'Property Manager Last Name',
                        'Property Manager Job Title',
                        'Property Manager Email',
                        'Property Manager Work Phone',
                        'Property Manager Mobile Phone'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],
            'Tenant Statement' => [
                'mergeFields' => [
                    'Statement' => [
                        'Statement Date Generated',
                        'Statement Number',
                        'Statement Received Amount',
                        'Statement Link'
                    ],
                    'Date' => ['Date Today'],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Tenant' => [
                        'Tenant Abn',
                        'Tenant Company Name',
                        'Tenant Website',
                        'Tenant Physical Address',
                        'Tenant Postal Address',
                        'Tenant Email',
                        'Tenant Phone Numbers',
                        'Tenant First Name',
                        'Tenant Last Name',
                        'Tenant Salutation',
                        'Tenant All Names',
                        'Tenant Address Block',
                        'Tenant Bank Reference'
                    ],
                    'Property Manager' => [
                        'Property Manager First Name',
                        'Property Manager Last Name',
                        'Property Manager Job Title',
                        'Property Manager Email',
                        'Property Manager Work Phone',
                        'Property Manager Mobile Phone'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],

            'Owner Statement' => [
                'mergeFields' => [
                    'Statement' => [
                        'Statement Date Generated',
                        'Statement Number',
                        'Statement Received Amount',
                        'Statement Link'
                    ],
                    'Date' => ['Date Today'],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Owner' => [
                        'Owner Abn',
                        'Owner Company Name',
                        'Owner Website',
                        'Owner Physical Address',
                        'Owner Postal Address',
                        'Owner Email',
                        'Owner Phone Numbers',
                        'Owner First Name',
                        'Owner Last Name',
                        'Owner Salutation',
                        'Owner All Names',
                        'Owner Address Block'
                    ],
                    'Property Manager' => [
                        'Property Manager First Name',
                        'Property Manager Last Name',
                        'Property Manager Job Title',
                        'Property Manager Email',
                        'Property Manager Work Phone',
                        'Property Manager Mobile Phone'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],

            'Supplier Statement' => [
                'mergeFields' => [
                    'Statement' => [
                        'Statement Date Generated',
                        'Statement Number',
                        'Statement Received Amount',
                        'Statement Link'
                    ],
                    'Date' => ['Date Today'],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Supplier' => [
                        'Supplier Abn',
                        'Supplier Company Name',
                        'Supplier Website',
                        'Supplier Physical Address',
                        'Supplier Postal Address',
                        'Supplier Email',
                        'Supplier Phone Numbers',
                        'Supplier First Name',
                        'Supplier Last Name',
                        'Supplier Salutation',
                        'Supplier All Names',
                        'Supplier Address Block'
                    ],
                    'Property Manager' => [
                        'Property Manager First Name',
                        'Property Manager Last Name',
                        'Property Manager Job Title',
                        'Property Manager Email',
                        'Property Manager Work Phone',
                        'Property Manager Mobile Phone'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],
            'Folio Receipt' => [
                'mergeFields' => [
                    'Receipt' => [
                        'Receipt Number',
                        'Receipt Issue Date',
                        'Receipt Gross Amount',
                        'Receipt Link'
                    ],
                    'Date' => ['Date Today'],
                    'Property' => [
                        'Property Single Line',
                        'Property Multi Line',
                        'Property Key Number'
                    ],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ],
            'Owner Financial Activity' => [
                'mergeFields' => [
                    'Statement' => [
                        'Financial Summary Link',
                        'Financial Summary Start Date',
                        'Financial Summary End Date',
                        'Financial Summary Net Position'
                    ],
                    'Date' => ['Date Today'],
                    'Agent' => [
                        'Agent Abn',
                        'Agent Company Name',
                        'Agent Website',
                        'Agent Physical Address',
                        'Agent Postal Address',
                        'Agent Email',
                        'Agent Phone Numbers',
                        'Agent First Name',
                        'Agent Last Name',
                        'Agent Salutation',
                        'Agent All Names',
                        'Agent Address Block',
                        'Agent Client Access Sentence'
                    ],
                    'Property Manager' => [
                        'Property Manager First Name',
                        'Property Manager Last Name',
                        'Property Manager Job Title',
                        'Property Manager Email',
                        'Property Manager Work Phone',
                        'Property Manager Mobile Phone'
                    ],
                    'Owner' => [
                        'Owner Abn',
                        'Owner Company Name',
                        'Owner Website',
                        'Owner Physical Address',
                        'Owner Postal Address',
                        'Owner Email',
                        'Owner Phone Numbers',
                        'Owner First Name',
                        'Owner Last Name',
                        'Owner Salutation',
                        'Owner All Names',
                        'Owner Address Block'
                    ],
                    'Send From' => [
                        'Send From First Name',
                        'Send From Last Name',
                        'Send From Job Title',
                        'Send From Portfolio Name',
                        'Send From Email',
                        'Send From Work Phone',
                        'Send From Mobile Phone'
                    ],
                    'Recipient' => [
                        'Recipient Abn',
                        'Recipient Company Name',
                        'Recipient Website',
                        'Recipient Physical Address',
                        'Recipient Postal Address',
                        'Recipient Email',
                        'Recipient Phone Numbers',
                        'Recipient First Name',
                        'Recipient Last Name',
                        'Recipient Salutation',
                        'Recipient All Names',
                        'Recipient Address Block'
                    ]
                ]
            ]
        ];


        foreach ($messageActions as $actionName => $data) {
            // Create MessageAction
            $messageAction = MessageAction::create(['name' => $actionName]);

            // Create MergeFields and MergeSubfields
            foreach ($data['mergeFields'] as $mergeFieldName => $subfields) {
                $mergeField = MergeField::create([
                    'name' => $mergeFieldName,
                    'message_action_id' => $messageAction->id,
                ]);

                foreach ($subfields as $subfieldName) {
                    MergeSubfield::create([
                        'name' => $subfieldName,
                        'merge_field_id' => $mergeField->id,
                    ]);
                }
            }
        }
    }
}
