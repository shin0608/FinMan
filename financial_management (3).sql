-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 09, 2025 at 09:11 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `financial_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_type` enum('Asset','Liability','Equity','Revenue','Expense') NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `description` text DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 1 COMMENT '1 for current assets/liabilities, 0 for non-current'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `account_code`, `account_name`, `account_type`, `balance`, `created_at`, `updated_at`, `status`, `description`, `is_current`) VALUES
(1, '1000', 'Accounts Receivable', 'Asset', -166178.00, '2025-05-05 20:37:21', '2025-05-09 07:04:28', 'Active', 'tests', 1),
(2, '1100', 'Accounts Receivable', 'Asset', 0.00, '2025-05-05 20:37:21', '2025-05-05 20:37:21', 'Active', NULL, 1),
(3, '2000', 'Accounts Payable', 'Liability', 0.00, '2025-05-05 20:37:21', '2025-05-05 20:37:21', 'Active', NULL, 1),
(4, '3000', 'Capital', 'Equity', 600.00, '2025-05-05 20:37:21', '2025-05-05 20:37:21', 'Active', NULL, 1),
(5, '4000', 'Revenue', '', 0.00, '2025-05-05 20:37:21', '2025-05-05 20:37:21', 'Active', NULL, 1),
(6, '5000', 'Expenses', 'Expense', 157778.00, '2025-05-05 20:37:21', '2025-05-09 07:04:28', 'Active', NULL, 1),
(8, '1000', 'Cash', 'Asset', 9000.00, '2025-05-07 07:44:32', '2025-05-09 03:51:40', 'Active', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `account_types`
--

CREATE TABLE `account_types` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account_types`
--

INSERT INTO `account_types` (`id`, `type`, `name`, `description`) VALUES
(1, 'Asset', 'Cash on Hand', 'Physical currency held by the business'),
(2, 'Asset', 'Cash in Bank', 'Money held in bank accounts'),
(3, 'Asset', 'Accounts Receivable', 'Money owed by customers'),
(4, 'Asset', 'Inventory', 'Goods available for sale'),
(5, 'Asset', 'Prepaid Expenses', 'Expenses paid in advance'),
(6, 'Asset', 'Equipment', 'Business equipment owned'),
(7, 'Asset', 'Furniture and Fixtures', 'Office furniture and fixtures'),
(8, 'Asset', 'Vehicles', 'Company vehicles'),
(9, 'Asset', 'Buildings', 'Company-owned buildings'),
(10, 'Asset', 'Land', 'Company-owned land'),
(11, 'Liability', 'Accounts Payable', 'Money owed to suppliers'),
(12, 'Liability', 'Notes Payable', 'Formal debt instruments'),
(13, 'Liability', 'Accrued Expenses', 'Expenses incurred but not paid'),
(14, 'Liability', 'Unearned Revenue', 'Advanced payments from customers'),
(15, 'Liability', 'Long-term Loans', 'Loans due after one year'),
(16, 'Liability', 'Mortgage Payable', 'Property-secured loans'),
(17, 'Equity', 'Common Stock', 'Shares issued to owners'),
(18, 'Equity', 'Retained Earnings', 'Accumulated profits'),
(19, 'Equity', 'Owner\'s Capital', 'Owner\'s investment'),
(20, 'Equity', 'Owner\'s Drawing', 'Owner\'s withdrawals'),
(21, 'Income', 'Sales Revenue', 'Revenue from main business activities'),
(22, 'Income', 'Service Revenue', 'Revenue from services'),
(23, 'Income', 'Interest Income', 'Income from investments'),
(24, 'Income', 'Rental Income', 'Income from property rentals'),
(25, 'Income', 'Commission Income', 'Income from commissions'),
(26, 'Expense', 'Salaries Expense', 'Employee salaries'),
(27, 'Expense', 'Rent Expense', 'Rental payments'),
(28, 'Expense', 'Utilities Expense', 'Utility costs'),
(29, 'Expense', 'Office Supplies Expense', 'Office supply costs'),
(30, 'Expense', 'Insurance Expense', 'Insurance premiums'),
(31, 'Expense', 'Advertising Expense', 'Marketing costs'),
(32, 'Expense', 'Depreciation Expense', 'Asset value reduction'),
(33, 'Expense', 'Interest Expense', 'Interest paid on loans');

-- --------------------------------------------------------

--
-- Table structure for table `cash_disbursement`
--

CREATE TABLE `cash_disbursement` (
  `id` int(11) NOT NULL,
  `payee` varchar(255) NOT NULL,
  `disbursement_date` date NOT NULL,
  `voucher_number` varchar(50) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `void_reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cash_disbursement`
--

INSERT INTO `cash_disbursement` (`id`, `payee`, `disbursement_date`, `voucher_number`, `amount`, `created_by`, `status`, `void_reason`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, 'Test Payee 1', '2025-05-07', 'V001', 1000.00, NULL, 'Completed', NULL, NULL, NULL, '2025-05-07 12:10:38', '2025-05-07 12:10:38');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `code`) VALUES
(1, 'College of Engineering', 'ENG'),
(2, 'College of Business', 'BUS'),
(3, 'College of Arts and Sciences', 'CAS'),
(4, 'College of Education', 'EDU'),
(5, 'College of Information Technology', 'CIT');

-- --------------------------------------------------------

--
-- Table structure for table `disbursements`
--

CREATE TABLE `disbursements` (
  `id` int(11) NOT NULL,
  `voucher_number` varchar(20) NOT NULL,
  `disbursement_date` date NOT NULL,
  `payee` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `status` enum('Pending','Completed','Voided') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `void_reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disbursements`
--

INSERT INTO `disbursements` (`id`, `voucher_number`, `disbursement_date`, `payee`, `amount`, `description`, `status`, `created_at`, `void_reason`, `approved_by`, `approved_at`, `updated_at`, `created_by`) VALUES
(1, 'CD2025040001', '2025-04-03', 'Electric Company', 5000.00, NULL, 'Voided', '2025-05-05 20:37:21', NULL, NULL, NULL, '2025-05-08 20:02:23', NULL),
(2, 'CD2025030005', '2025-03-15', 'Office Supplies Inc', 1200.00, NULL, '', '2025-05-05 20:37:21', NULL, NULL, NULL, '2025-05-08 20:02:23', NULL),
(3, 'CD2025030004', '2025-03-02', 'Payroll', 40000.00, NULL, '', '2025-05-05 20:37:21', NULL, NULL, NULL, '2025-05-08 20:02:23', NULL),
(4, 'CD2025030003', '2025-02-28', 'Internet Provider', 1500.00, NULL, '', '2025-05-05 20:37:21', NULL, NULL, NULL, '2025-05-08 20:02:23', NULL),
(5, 'CD202505070001', '2025-05-07', 'EJ', 5000.00, NULL, '', '2025-05-07 01:37:23', NULL, NULL, NULL, '2025-05-08 20:02:23', NULL),
(6, 'CD202505070002', '2025-05-07', 'Payroll', 50000.00, NULL, '', '2025-05-07 03:52:25', NULL, NULL, NULL, '2025-05-08 20:02:23', NULL),
(7, 'CD2505070001', '2025-05-07', 'Payroll', 5000.00, NULL, '', '2025-05-07 07:11:48', NULL, NULL, NULL, '2025-05-08 20:02:23', NULL),
(8, 'CD2505070002', '2025-05-07', 'Payroll', 50000.00, NULL, '', '2025-05-07 07:46:19', NULL, NULL, NULL, '2025-05-08 20:02:23', NULL),
(9, 'CD2505070003', '2025-05-07', 'Payroll', 4000.00, NULL, '', '2025-05-07 09:19:40', NULL, NULL, NULL, '2025-05-08 20:02:23', NULL),
(10, 'CD2505080001', '2025-05-08', 'Payroll', 50000.00, NULL, '', '2025-05-08 14:15:02', NULL, NULL, NULL, '2025-05-08 20:02:23', NULL),
(11, 'CD2505090001', '2025-05-09', 'Payroll', 5000.00, NULL, '', '2025-05-09 04:05:26', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `disbursement_details`
--

CREATE TABLE `disbursement_details` (
  `id` int(11) NOT NULL,
  `disbursement_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disbursement_entries`
--

CREATE TABLE `disbursement_entries` (
  `id` int(11) NOT NULL,
  `disbursement_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equity`
--

CREATE TABLE `equity` (
  `id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_history`
--

CREATE TABLE `financial_history` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `total_assets` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_liabilities` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_equity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_by` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_insights`
--

CREATE TABLE `financial_insights` (
  `id` int(11) NOT NULL,
  `statement_id` int(11) DEFAULT NULL,
  `insight_type` varchar(50) DEFAULT NULL,
  `insight_text` text DEFAULT NULL,
  `recommendation` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_logs`
--

CREATE TABLE `financial_logs` (
  `id` int(11) NOT NULL,
  `update_type` varchar(50) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `updated_by` varchar(100) NOT NULL,
  `update_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_statements`
--

CREATE TABLE `financial_statements` (
  `id` int(11) NOT NULL,
  `type` enum('income_statement','balance_sheet','cash_flow') NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `generated_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`generated_data`)),
  `analysis_text` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('draft','final') DEFAULT 'draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `receipt_number` varchar(20) NOT NULL,
  `payment_date` date NOT NULL,
  `payer` varchar(100) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reporting_logs`
--

CREATE TABLE `reporting_logs` (
  `id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `performed_by` varchar(100) NOT NULL,
  `activity_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_payments`
--

CREATE TABLE `student_payments` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `payment_date` date NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_type` enum('Tuition','Miscellaneous','Other') NOT NULL,
  `payment_method` enum('Cash','Check','Bank Transfer','Online') NOT NULL,
  `remaining_balance` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Pending','Completed','Voided') NOT NULL DEFAULT 'Completed',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `reference_number` varchar(20) NOT NULL,
  `entry_name` varchar(255) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `entry_type` varchar(50) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` enum('Posted','Pending','Voided') DEFAULT 'Pending',
  `created_by` varchar(100) DEFAULT 'System',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `posted_to_gl` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `reference_number`, `entry_name`, `transaction_date`, `entry_type`, `description`, `amount`, `status`, `created_by`, `created_at`, `posted_to_gl`) VALUES
(1, 'JE2025040002', 'JE2025040002', '2025-04-03', NULL, 'Initial deposit', 600.00, 'Posted', 'System', '2025-05-05 20:37:21', 0),
(2, 'JE2025040001', 'JE2025040001', '2025-04-03', NULL, 'Initial deposit', 600.00, 'Posted', 'System', '2025-05-05 20:37:21', 0),
(3, 'JE2025030010', 'JE2025030010', '2025-03-02', NULL, 'Electric Bill', 5000.00, 'Posted', 'System', '2025-05-05 20:37:21', 0),
(4, 'JE2025030009', 'JE2025030009', '2025-03-02', NULL, 'Salary for eb 16-28', 40000.00, 'Posted', 'System', '2025-05-05 20:37:21', 0),
(5, 'JE202505060001', 'JE202505060001', '2025-05-06', NULL, 'pay', 1000.00, 'Posted', 'Ediel Jade Deriquito', '2025-05-06 10:48:30', 0),
(6, 'JE202505060002', 'JE202505060002', '2025-05-06', NULL, 'cash', 50000.00, 'Voided', 'Ediel Jade Deriquito', '2025-05-06 10:50:00', 0),
(13, 'JE202505060003', 'JE202505060003', '2025-05-06', NULL, 'cash', 1000.00, 'Posted', '4', '2025-05-06 19:54:20', 0),
(18, 'JE202505070004', 'JE202505070004', '2025-05-07', NULL, 'test', 100.00, 'Voided', '4', '2025-05-06 23:12:41', 0),
(19, 'JE202505070005', 'JE202505070005', '2025-05-07', NULL, 'test', 1000.00, 'Voided', '4', '2025-05-06 23:13:35', 0),
(20, 'JE202505070006', 'JE202505070006', '2025-05-07', NULL, 'test', 1000.00, 'Voided', '4', '2025-05-06 23:39:08', 0),
(21, 'JE202505070007', 'JE202505070007', '2025-05-07', NULL, 'test', 222.00, 'Voided', '4', '2025-05-06 23:55:17', 0),
(22, 'JE202505070008', 'JE202505070008', '2025-05-07', NULL, 'For tools', 5000.00, 'Voided', 'Ediel Jade Deriquito', '2025-05-07 01:37:23', 0),
(23, 'JE202505070009', 'JE202505070009', '2025-05-07', NULL, 'Pasahod', 50000.00, 'Voided', 'Ediel Jade Deriquito', '2025-05-07 03:52:25', 0),
(24, 'JE2505070001', 'JE2505070001', '2025-05-07', NULL, 'Salary', 5000.00, 'Posted', 'Ediel Jade Deriquito', '2025-05-07 07:11:48', 0),
(25, 'JE2505070002', 'JE2505070002', '2025-05-07', NULL, 'Salary', 50000.00, 'Voided', 'Ediel Jade Deriquito', '2025-05-07 07:46:19', 0),
(26, 'JE2505070003', 'JE2505070003', '2025-05-07', NULL, 'salary\r\n', 4000.00, 'Voided', 'Ediel Jade Deriquito', '2025-05-07 09:19:40', 0),
(27, 'JE2505070004', 'JE2505070004', '2025-05-07', NULL, 'test', 1000.00, 'Voided', '5', '2025-05-07 10:21:32', 0),
(28, 'JE2505080001', 'JE2505080001', '2025-05-08', NULL, 'test', 1000.00, 'Voided', '5', '2025-05-08 11:27:57', 0),
(29, 'JE2505080002', 'JE2505080002', '2025-05-08', NULL, 'test', 50000.00, 'Posted', 'Ediel Jade Deriquito', '2025-05-08 14:15:02', 0),
(30, 'JE2505080003', 'JE2505080003', '2025-05-08', NULL, 'test', 1000.00, 'Voided', '4', '2025-05-08 17:59:15', 0),
(31, 'JE2505090001', 'JE2505090001', '2025-05-09', NULL, 'test', 1000.00, 'Posted', '4', '2025-05-09 01:28:08', 0),
(32, 'JE2505090002', NULL, '2025-05-09', NULL, 'test', 1000.00, 'Posted', '4', '2025-05-09 02:04:54', 0),
(34, 'JE2505090003', NULL, '2025-05-09', NULL, 'test', 10000.00, 'Voided', '4', '2025-05-09 03:51:40', 0),
(35, 'JE2505090004', NULL, '2025-05-09', NULL, 'salary', 5000.00, 'Voided', 'Ediel Jade Deriquito', '2025-05-09 04:05:26', 0),
(36, 'JE2505090005', NULL, '2025-05-09', NULL, 'test', 1000.00, 'Posted', '4', '2025-05-09 06:59:27', 0);

-- --------------------------------------------------------

--
-- Table structure for table `transaction_details`
--

CREATE TABLE `transaction_details` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `entry_date` date DEFAULT NULL,
  `account_id` int(11) NOT NULL,
  `entry_type` varchar(10) DEFAULT NULL,
  `particulars` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `debit_amount` decimal(15,2) DEFAULT 0.00,
  `credit_amount` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_details`
--

INSERT INTO `transaction_details` (`id`, `transaction_id`, `entry_date`, `account_id`, `entry_type`, `particulars`, `description`, `debit_amount`, `credit_amount`) VALUES
(1, 1, NULL, 1, NULL, NULL, NULL, 600.00, 0.00),
(2, 1, NULL, 4, NULL, NULL, NULL, 0.00, 600.00),
(3, 3, NULL, 6, NULL, NULL, NULL, 5000.00, 0.00),
(4, 3, NULL, 1, NULL, NULL, NULL, 0.00, 5000.00),
(5, 5, NULL, 2, NULL, NULL, NULL, 1000.00, 1000.00),
(6, 6, NULL, 1, NULL, NULL, NULL, 50000.00, 50000.00),
(13, 13, NULL, 1, NULL, NULL, NULL, 1000.00, 1000.00),
(14, 18, NULL, 6, 'INV', 'Internet', '', 100.00, 100.00),
(15, 18, NULL, 1, 'PAY', 'Investment', '', 0.00, 0.00),
(16, 19, NULL, 6, 'PAY', 'Internet', '', 1000.00, 1000.00),
(17, 19, NULL, 1, 'INV', 'Investment', '', 0.00, 0.00),
(18, 20, '2025-05-07', 6, 'PAY', 'Internet', '', 1000.00, 1000.00),
(19, 20, '2025-05-07', 1, 'INV', 'Investment', '', 0.00, 0.00),
(20, 21, '2025-05-07', 6, 'INV', 'Internet', '', 0.00, 222.00),
(21, 21, '2025-05-07', 1, 'PAY', 'Investment', '', 222.00, 0.00),
(22, 22, NULL, 6, NULL, NULL, NULL, 5000.00, 0.00),
(23, 22, NULL, 1, NULL, NULL, NULL, 0.00, 5000.00),
(24, 23, NULL, 6, NULL, NULL, NULL, 50000.00, 0.00),
(25, 23, NULL, 1, NULL, NULL, NULL, 0.00, 50000.00),
(26, 24, NULL, 6, NULL, NULL, NULL, 5000.00, 0.00),
(27, 24, NULL, 1, NULL, NULL, NULL, 0.00, 5000.00),
(28, 25, NULL, 6, NULL, NULL, NULL, 50000.00, 0.00),
(29, 25, NULL, 1, NULL, NULL, NULL, 0.00, 50000.00),
(30, 26, NULL, 6, NULL, NULL, NULL, 4000.00, 0.00),
(31, 26, NULL, 1, NULL, NULL, NULL, 0.00, 4000.00),
(32, 27, '2025-05-07', 6, 'INV', 'Internet', '', 1000.00, 0.00),
(33, 27, '2025-05-07', 8, 'PAY', 'Investment', '', 0.00, 1000.00),
(34, 28, '2025-05-08', 6, 'INV', 'test', '', 1000.00, 0.00),
(35, 28, '2025-05-08', 8, 'PAY', 'test', '', 0.00, 1000.00),
(36, 29, NULL, 6, NULL, NULL, NULL, 50000.00, 0.00),
(37, 29, NULL, 1, NULL, NULL, NULL, 0.00, 50000.00),
(38, 30, '2025-05-08', 1, 'INV', 'test', 'ass', 0.00, 1000.00),
(39, 30, '2025-05-08', 6, 'PAY', 'test', 'ass', 1000.00, 0.00),
(40, 31, '2025-05-09', 1, 'INV', 'test', 'tes', 1000.00, 0.00),
(41, 31, '2025-05-09', 6, 'PAY', 'test', 'test', 0.00, 1000.00),
(42, 32, '2025-05-09', 8, 'INV', 'Internet', 'test', 1000.00, 0.00),
(43, 32, '2025-05-09', 6, 'PAY', 'test', 'tes', 0.00, 1000.00),
(45, 34, '2025-05-09', 8, 'INV', 'Internet', '', 10000.00, 0.00),
(46, 34, '2025-05-09', 6, 'PAY', 'test', '', 0.00, 10000.00),
(47, 35, NULL, 6, NULL, NULL, NULL, 5000.00, 0.00),
(48, 35, NULL, 1, NULL, NULL, NULL, 0.00, 5000.00),
(49, 36, '2025-05-09', 1, 'INV', 'Internet', '', 1000.00, 0.00),
(50, 36, '2025-05-09', 6, 'PAY', 'test', '', 0.00, 1000.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','accountant','user') DEFAULT 'user',
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('pending','active','inactive') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `age`, `department_id`, `email`, `role`, `profile_picture`, `status`, `created_at`, `active`) VALUES
(1, 'admin', '$2y$10$8KzO1f6TgWVJU7j0YX9kT.RA9KkXS5RlvioGfMN9Z/XCVv9yLQp4W', 'System Administrator', NULL, NULL, 'admin@example.com', 'admin', NULL, 'pending', '2025-05-05 20:37:21', 1),
(4, 'admin123', '$2y$10$psJc3dBrzpriXdKxcIl0FuoPCLth8kynmWrj/Pcbb8iOeDbDtEYae', 'Ediel Jade Deriquito', NULL, NULL, 'dedieljade@gmail.com', 'admin', 'uploads/profiles/profile_681d9a20e4f2e.png', 'pending', '2025-05-06 05:09:33', 1),
(5, 'test', '$2y$10$zyjKRWfsG3E7HBbDLB9Q3.nAbkGcajgEmyMnvD1PWi3D/dT/zedR2', 'EJ Deriquito', 21, 3, 'test@gmail.com', 'accountant', NULL, 'pending', '2025-05-06 23:16:53', 1),
(6, 'try', '$2y$10$fC/FT08TG//6gddKWmgXN.KCRwJ5.JlJxj/voQ9Bh4abRfVFyNPba', 'EJ EJ', 23, NULL, 'try123@gmail.com', 'admin', NULL, 'pending', '2025-05-06 23:29:33', 1),
(7, 'mamamo', '$2y$10$C3x159EpvZehApBeAH4TUu5E77wJS8U4zwVHJr3zaeYUzzYZgNVna', 'hfjhkgkfcgkcjng', 100, NULL, 'jodideriquito@gmail.com', 'accountant', NULL, 'active', '2025-05-08 11:32:53', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_access_logs`
--

CREATE TABLE `user_access_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `logout_time` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_access_logs`
--

INSERT INTO `user_access_logs` (`id`, `user_id`, `login_time`, `logout_time`, `ip_address`, `user_agent`) VALUES
(1, 4, '2025-05-06 05:12:04', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(2, 4, '2025-05-06 05:12:04', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(3, 4, '2025-05-06 17:08:25', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(4, 4, '2025-05-06 17:08:25', '2025-05-06 17:47:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(5, 4, '2025-05-06 17:48:03', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(6, 4, '2025-05-06 17:48:03', '2025-05-06 19:05:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(7, 4, '2025-05-06 19:05:59', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(8, 4, '2025-05-06 19:05:59', '2025-05-06 23:15:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(9, 5, '2025-05-06 23:17:02', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(10, 5, '2025-05-06 23:17:02', '2025-05-06 23:18:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(11, 4, '2025-05-06 23:18:15', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(12, 4, '2025-05-06 23:18:15', '2025-05-06 23:26:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(13, 5, '2025-05-06 23:28:06', '2025-05-06 23:28:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(14, 6, '2025-05-06 23:29:44', '2025-05-06 23:30:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(15, 4, '2025-05-06 23:31:09', '2025-05-07 00:38:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(16, 5, '2025-05-07 00:38:27', '2025-05-07 00:58:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(17, 4, '2025-05-07 00:58:28', '2025-05-07 01:33:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(18, 4, '2025-05-07 01:33:57', '2025-05-07 01:34:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(19, 4, '2025-05-07 01:34:14', '2025-05-07 07:08:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(20, 4, '2025-05-07 07:08:39', '2025-05-07 10:15:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(21, 4, '2025-05-07 10:15:21', '2025-05-07 10:20:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(22, 4, '2025-05-07 10:20:33', '2025-05-07 10:20:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(23, 5, '2025-05-07 10:20:44', '2025-05-07 10:22:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(24, 4, '2025-05-07 10:22:13', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(25, 4, '2025-05-08 11:24:24', '2025-05-08 11:27:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(26, 5, '2025-05-08 11:27:23', '2025-05-08 11:28:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(27, 4, '2025-05-08 11:28:30', '2025-05-08 11:31:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(28, 7, '2025-05-08 11:34:42', '2025-05-08 11:35:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(29, 4, '2025-05-08 11:36:07', '2025-05-08 11:41:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(30, 4, '2025-05-08 13:25:08', '2025-05-08 13:26:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(31, 4, '2025-05-08 13:26:41', '2025-05-08 20:17:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(32, 5, '2025-05-08 20:18:03', '2025-05-08 20:18:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(33, 4, '2025-05-08 20:18:32', '2025-05-09 02:21:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(34, 5, '2025-05-09 02:21:27', '2025-05-09 02:28:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(35, 4, '2025-05-09 02:28:37', '2025-05-09 04:29:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(36, 5, '2025-05-09 04:29:56', '2025-05-09 04:30:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(37, 4, '2025-05-09 04:30:47', '2025-05-09 04:46:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(38, 5, '2025-05-09 04:46:29', '2025-05-09 05:05:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(39, 4, '2025-05-09 05:05:19', '2025-05-09 05:05:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(40, 5, '2025-05-09 05:05:29', '2025-05-09 05:05:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(41, 4, '2025-05-09 05:05:46', '2025-05-09 05:32:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(42, 5, '2025-05-09 05:32:37', '2025-05-09 05:33:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(43, 4, '2025-05-09 05:33:10', '2025-05-09 06:00:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(44, 4, '2025-05-09 06:00:47', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36');

-- --------------------------------------------------------

--
-- Table structure for table `void_requests`
--

CREATE TABLE `void_requests` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `requested_date` datetime DEFAULT current_timestamp(),
  `processed_by` int(11) DEFAULT NULL,
  `processed_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `void_requests`
--

INSERT INTO `void_requests` (`id`, `transaction_id`, `requested_by`, `reason`, `status`, `requested_date`, `processed_by`, `processed_date`) VALUES
(1, 18, 4, 'error', 'Approved', '2025-05-07 08:35:28', 4, '2025-05-07 09:03:37'),
(2, 13, 5, 'error', 'Rejected', '2025-05-07 08:38:41', 4, '2025-05-07 09:03:47'),
(3, 1, 5, 'error', 'Rejected', '2025-05-07 08:46:09', 4, '2025-05-07 09:04:01'),
(4, 19, 5, 'error', 'Rejected', '2025-05-07 08:58:17', 4, '2025-05-07 09:03:54'),
(5, 20, 4, 'error', 'Approved', '2025-05-07 09:00:29', NULL, NULL),
(6, 21, 4, 'error', 'Approved', '2025-05-07 09:04:16', NULL, NULL),
(7, 19, 4, 'error', 'Approved', '2025-05-07 09:25:27', NULL, NULL),
(8, 6, 4, 'error', 'Approved', '2025-05-07 10:19:39', NULL, NULL),
(9, 27, 5, 'error', 'Approved', '2025-05-07 18:21:40', 4, '2025-05-07 18:23:34'),
(10, 26, 5, 'error', 'Approved', '2025-05-07 18:21:48', 4, '2025-05-07 18:23:29'),
(11, 25, 4, 'error', 'Approved', '2025-05-08 19:26:33', NULL, NULL),
(12, 28, 5, 'error', 'Approved', '2025-05-08 19:28:09', 4, '2025-05-08 19:28:59'),
(13, 22, 7, 'Erroe', 'Approved', '2025-05-08 19:35:45', 4, '2025-05-08 19:36:33'),
(14, 23, 4, 'Error', 'Approved', '2025-05-08 19:38:24', NULL, NULL),
(15, 30, 4, 'error', 'Approved', '2025-05-09 02:58:39', NULL, NULL),
(16, 35, 4, 'error', 'Approved', '2025-05-09 13:32:03', NULL, NULL),
(17, 34, 5, 'error', 'Approved', '2025-05-09 13:32:52', 4, '2025-05-09 13:48:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `account_types`
--
ALTER TABLE `account_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_name_per_type` (`type`,`name`);

--
-- Indexes for table `cash_disbursement`
--
ALTER TABLE `cash_disbursement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_cash_disbursement_status` (`status`),
  ADD KEY `idx_cash_disbursement_date` (`disbursement_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date` (`disbursement_date`),
  ADD KEY `idx_disbursement_date` (`disbursement_date`),
  ADD KEY `idx_disbursement_status` (`status`),
  ADD KEY `idx_disbursement_voucher` (`voucher_number`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `disbursements`
--
ALTER TABLE `disbursements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_disbursement_status` (`status`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_disbursement_date` (`disbursement_date`),
  ADD KEY `idx_voucher_number` (`voucher_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date` (`disbursement_date`),
  ADD KEY `idx_voucher` (`voucher_number`);

--
-- Indexes for table `disbursement_details`
--
ALTER TABLE `disbursement_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `disbursement_id` (`disbursement_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `disbursement_entries`
--
ALTER TABLE `disbursement_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_disbursement_entries_disbursement` (`disbursement_id`),
  ADD KEY `idx_disbursement_entries_account` (`account_id`);

--
-- Indexes for table `equity`
--
ALTER TABLE `equity`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `financial_history`
--
ALTER TABLE `financial_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `financial_insights`
--
ALTER TABLE `financial_insights`
  ADD PRIMARY KEY (`id`),
  ADD KEY `statement_id` (`statement_id`);

--
-- Indexes for table `financial_logs`
--
ALTER TABLE `financial_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `financial_statements`
--
ALTER TABLE `financial_statements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_date` (`payment_date`);

--
-- Indexes for table `reporting_logs`
--
ALTER TABLE `reporting_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_payments`
--
ALTER TABLE `student_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transactions_entry_name` (`entry_name`),
  ADD KEY `idx_transactions_reference_number` (`reference_number`);

--
-- Indexes for table `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_access_logs`
--
ALTER TABLE `user_access_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `void_requests`
--
ALTER TABLE `void_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_void_requests_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `account_types`
--
ALTER TABLE `account_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `cash_disbursement`
--
ALTER TABLE `cash_disbursement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `disbursements`
--
ALTER TABLE `disbursements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `disbursement_details`
--
ALTER TABLE `disbursement_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `disbursement_entries`
--
ALTER TABLE `disbursement_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `equity`
--
ALTER TABLE `equity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_history`
--
ALTER TABLE `financial_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_insights`
--
ALTER TABLE `financial_insights`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_logs`
--
ALTER TABLE `financial_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_statements`
--
ALTER TABLE `financial_statements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reporting_logs`
--
ALTER TABLE `reporting_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_payments`
--
ALTER TABLE `student_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `transaction_details`
--
ALTER TABLE `transaction_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_access_logs`
--
ALTER TABLE `user_access_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `void_requests`
--
ALTER TABLE `void_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cash_disbursement`
--
ALTER TABLE `cash_disbursement`
  ADD CONSTRAINT `cash_disbursement_ibfk_1` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `cash_disbursement_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `disbursements`
--
ALTER TABLE `disbursements`
  ADD CONSTRAINT `disbursements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `disbursement_details`
--
ALTER TABLE `disbursement_details`
  ADD CONSTRAINT `disbursement_details_ibfk_1` FOREIGN KEY (`disbursement_id`) REFERENCES `disbursements` (`id`),
  ADD CONSTRAINT `disbursement_details_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`);

--
-- Constraints for table `disbursement_entries`
--
ALTER TABLE `disbursement_entries`
  ADD CONSTRAINT `disbursement_entries_ibfk_1` FOREIGN KEY (`disbursement_id`) REFERENCES `cash_disbursement` (`id`),
  ADD CONSTRAINT `disbursement_entries_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`);

--
-- Constraints for table `financial_insights`
--
ALTER TABLE `financial_insights`
  ADD CONSTRAINT `financial_insights_ibfk_1` FOREIGN KEY (`statement_id`) REFERENCES `financial_statements` (`id`);

--
-- Constraints for table `financial_statements`
--
ALTER TABLE `financial_statements`
  ADD CONSTRAINT `financial_statements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `student_payments`
--
ALTER TABLE `student_payments`
  ADD CONSTRAINT `student_payments_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD CONSTRAINT `transaction_details_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_details_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`);

--
-- Constraints for table `user_access_logs`
--
ALTER TABLE `user_access_logs`
  ADD CONSTRAINT `user_access_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `void_requests`
--
ALTER TABLE `void_requests`
  ADD CONSTRAINT `void_requests_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`),
  ADD CONSTRAINT `void_requests_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `void_requests_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
