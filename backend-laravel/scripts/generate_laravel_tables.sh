#!/bin/bash

# Usage: run from the project root (backend-laravel).
# It will create a Laravel model and a corresponding migration for every table
# listed below. You still need to edit each migration to add the columns defined
# by your supervisor and update the model with relationships / fillable fields.

set -e

# list of table names exactly as they should appear in the database
tables=(
  users password_resets failed_jobs settings departments designations documents
  branches employees employee_documents awards award_types termination_types
  terminations resignations travels promotions transfers warnings complaints
  payslip_types allowance_options loan_options deduction_options
  genrate_payslip_options set_salaries allowances commissions loans
  saturation_deductions other_payments overtimes pay_slips account_lists
  payees payers income_types expense_types deposits payment_types expenses
  transfer_balances events announcements leave_types leaves meetings tickets
  ticket_replies meeting_employees event_employees announcement_employees
  attendance_employees plans orders time_sheets coupons user_coupons assets
  ducument_uploads indicators appraisals goal_types goal_trackings
  company_policies training_types competencies performance__types zoom_meetings
  email_templates email_template_langs user_email_templates contract_types
  contracts contract_attechments contract_comments contract_notes
  generate_offer_letters joining_letters experience_certificates noc_certificates
  ch_favorites ch_messages login_details webhooks notification_templates
  notification_template_langs template languages referral_settings
  referral_transactions transaction_orders
)

for t in "${tables[@]}"; do
  # convert snake_case to StudlyCase for model names
  model=$(echo "$t" | sed -r 's/(^|_)([a-z])/'$'\U\2'/g)
  echo "Creating model $model and migration for table $t"
  php artisan make:model $model -m --quiet
  # note: artisan names the migration create_<table>_table automatically
done

echo "Finished generating models and empty migrations. Next step: edit the
migration files and add the column definitions from your schema blueprint."
