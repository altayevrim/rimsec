CREATE TABLE `ip_details` (
  `id` int(10) NOT NULL,
  `ip` varchar(20) NOT NULL,
  `reason` varchar(50) NOT NULL,
  `created` datetime NOT NULL,
  `banend` datetime NOT NULL,
  `priority` int(2) NOT NULL,
  `active` int(1) NOT NULL,
  `permanent` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE `ip_details`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `ip_details`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;