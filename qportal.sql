-- phpMyAdmin SQL Dump
-- version 2.11.7
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 30. Dezember 2008 um 02:08
-- Server Version: 5.0.51
-- PHP-Version: 5.2.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Datenbank: `surface`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur f�r Tabelle `attrib_collection`
--

CREATE TABLE IF NOT EXISTS `attrib_collection` (
  `autoid` int(11) NOT NULL auto_increment,
  `id` varchar(20) NOT NULL default '',
  `name` varchar(20) NOT NULL default '',
  `value` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`autoid`),
  KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Daten f�r Tabelle `attrib_collection`
--

INSERT INTO `attrib_collection` (`autoid`, `id`, `name`, `value`) VALUES
(1, 'left30', 'style', 'position:relativ;color:blue;');

-- --------------------------------------------------------

--
-- Tabellenstruktur f�r Tabelle `connect_collection`
--

CREATE TABLE IF NOT EXISTS `connect_collection` (
  `autoid` int(11) NOT NULL auto_increment,
  `tagid` varchar(20) NOT NULL default '',
  `attribid` varchar(20) NOT NULL default '',
  PRIMARY KEY  (`autoid`),
  KEY `tagid` (`tagid`),
  KEY `attribid` (`attribid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Daten f�r Tabelle `connect_collection`
--

INSERT INTO `connect_collection` (`autoid`, `tagid`, `attribid`) VALUES
(1, 'shiftleft', 'left30');

-- --------------------------------------------------------

--
-- Tabellenstruktur f�r Tabelle `precache`
--

CREATE TABLE IF NOT EXISTS `precache` (
  `name` varchar(255) character set latin1 collate latin1_bin NOT NULL,
  `best_before` timestamp NULL default NULL,
  `value` text character set latin1 collate latin1_bin NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Daten f�r Tabelle `precache`
--

INSERT INTO `precache` (`name`, `best_before`, `value`) VALUES
('none', '2008-10-25 22:35:18', 0x6669727374);

-- --------------------------------------------------------

--
-- Tabellenstruktur f�r Tabelle `tag_collection`
--

CREATE TABLE IF NOT EXISTS `tag_collection` (
  `id` varchar(20) NOT NULL default '',
  `attrib` varchar(20) NOT NULL default '',
  `content_ref` int(11) default NULL,
  `type` varchar(20) NOT NULL default '',
  `order` int(11) NOT NULL default '0',
  `group` varchar(20) NOT NULL default '',
  `ref` varchar(20) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `group` (`group`),
  KEY `lang` (`content_ref`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Daten f�r Tabelle `tag_collection`
--

INSERT INTO `tag_collection` (`id`, `attrib`, `content_ref`, `type`, `order`, `group`, `ref`) VALUES
('dbo', 'shiftleft', 1, 'pre', 0, 'dbo', ''),
('xmldo', 'shiftleft', 2, 'pre', 0, 'xmldo', ''),
('gk3', 'shiftleft', 3, 'pre', 0, 'gk3', ''),
('dbo2', 'shiftleft', 4, 'pre', 0, 'dbo2', ''),
('Request', 'shiftleft', 5, 'pre', 0, 'Request', ''),
('dbo3', 'shiftleft', 6, 'pre', 0, 'dbo3', '');

-- --------------------------------------------------------

--
-- Tabellenstruktur f�r Tabelle `tag_content`
--

CREATE TABLE IF NOT EXISTS `tag_content` (
  `id` int(11) NOT NULL auto_increment,
  `lang` varchar(4) NOT NULL default '',
  `content` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

--
-- Daten f�r Tabelle `tag_content`
--

INSERT INTO `tag_content` (`id`, `lang`, `content`) VALUES
(1, '', '				<object id="xml" name="XMLDO"  >\r\n					<param name="XMLTEMPLATE" >out</param>\r\n					<param name="LIST" ><object id="GK3" ><param name="ITER"/></object></param>\r\n					<param name="tag_in" >x</param ><param name="xpath" >GK3_X</param><param name="data" /><param name="content" >GK3_x</param><param name="tag_out" >id</param >\r\n					<param name="tag_in" >y</param ><param name="xpath" >GK3_Y</param><param name="data" /><param name="content" >GK3_y</param><param name="tag_out" >id</param >\r\n					<param name="tag_in" >z</param ><param name="xpath" >GK3_Z</param><param name="data" /><param name="content" >GK3_z</param><param name="tag_out" >posx</param >\r\n				</object>'),
(2, '', '				<object id="param" name="XMLDO" src="plugin_xmldo.php" >\r\n					<param name="XMLTEMPLATE" >in</param>\r\n					<param name="collection" >GK3_in</param>\r\n					<param name="tag_in" >strasse</param ><param name="xpath" >STRASSE</param><param name="value" ><object name="Request" ><param name="out" >strasse</param></object></param><param name="tag_out" >id</param >\r\n					<param name="tag_in" >hausnummer</param ><param name="xpath" >HAUSNUMMER</param><param name="value" ><object name="Request" ><param name="out" >hausnummer</param></object></param><param name="tag_out" >posx</param >\r\n					<param name="tag_in" >zusatz</param ><param name="xpath" >ZUSATZ</param><param name="value" ><object name="Request" ><param name="out" >zusatz</param></object></param><param name="tag_out" >posy</param >\r\n					<param name="tag_in" >ort</param ><param name="xpath" >ORT</param><param name="value" ><object name="Request" ><param name="out" >ort</param></object></param><param name="tag_out" >posy</param >\r\n					<param name="tag_in" >PLZ</param ><param name="xpath" >PLZ</param><param name="value" ><object name="Request" ><param name="out" >plz</param></object></param><param name="tag_out" >posy</param >\r\n				</object>'),
(3, '', '				<object id="GK3" name="GK3" src="plugin_GK3.php" >\r\n				<param name="LIST" ><object id="param" ><param name="ITER"/></object></param>\r\n					<param name="require" >strasse</param >\r\n					<param name="require" >hausnummer</param >\r\n					<param name="require" >zusatz</param >\r\n					<param name="require" >PLZ</param >\r\n					<param name="require" >ort</param >\r\n					<param name="content" >GK3_x</param>\r\n					<param name="content" >GK3_y</param>\r\n					<param name="content" >GK3_z</param>\r\n				</object>'),
(4, '', '		<object id="send" name="DBO" src="plugin_dbo.php" >\r\n		<param name="SQL" >SELECT tbl_drawings.id, tbl_drawings.type, tbl_drawings.x, tbl_drawings.y, tbl_drawings.parameter FROM tbl_drawings WHERE type&gt;0 AND tbl_drawings.id&gt;<object id="pointer" ><param name="SESSIONOUT" >pointer</param></object> \r\n			AND id_joiner != <object id="trans" ><param name="SESSIONOUT" >id</param></object> AND tbl_drawings.id_channel = <object id="trans" ><param name="SESSIONOUT" >channelid</param></object> ORDER BY tbl_drawings.id LIMIT 0 , 1000 ;</param>\r\n		</object>'),
(5, '', '	<content name="div"  >\r\n		<param name="id" >foot</param>\r\n		<element type="xhtml" >\r\n			<param name="style" >position:absolute;top:<object name="Request" id="eval" ><param name="eval" >return <object id="filescan" ><param name="many" /></object> * 33 + 560 ;</param></object>px;left:0px;width:100%;height:100px;background-color:#663333;</param>\r\n		</element>\r\n	</content>'),
(6, '', '		<object  id="xml"  >\r\n		<param name="collection" >create</param>\r\n		<param name="tag_in" >joiner</param ><param name="xpath" >nick</param><param name="data" /><param name="tag_out" />\r\n		<param name="tag_in" >pwst</param ><param name="xpath" >pass</param><param name="data" /><param name="tag_out" />\r\n		</object>\r\n		\r\n		\r\n		<object id="reciepe" name="DBO" src="plugin_dbo.php" >\r\n		<param name="SQL" >SELECT * FROM tbl_joiners;</param>\r\n		<param name="LIST" ><object id="xml" ><param name="ITER"/></object></param>\r\n		<param name="tag_in" >name</param ><param name="content" >joiner</param><param name="field" >tbl_joiners.name</param><param name="tag_out" />\r\n		<param name="tag_in" >pwst</param ><param name="content" >pwst</param><param name="field" >tbl_joiners.pwst</param><param name="tag_out" />\r\n		</object>');
CREATE TABLE IF NOT EXISTS `tbl_desc` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `desc` char(100) CHARACTER SET latin1 COLLATE latin1_german1_ci NOT NULL,
  `symbol_url` char(100) CHARACTER SET latin1 COLLATE latin1_german1_ci NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

INSERT INTO `tbl_desc` (`ID`, `desc`, `symbol_url`) VALUES
(1, 'Kontakt', 'img/kontakt.png'),
(2, 'Objekt', 'img/object.png'),
(3, 'Kommentar', 'img/comment.png');

--
-- Tabellenstruktur f�r Tabelle `tbl_group_management`
--

CREATE TABLE IF NOT EXISTS `tbl_group_management` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `groupname` char(60) NOT NULL,
  `groupdescription` char(255) NOT NULL,
  `sector` char(255) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `groupname` (`groupname`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Daten f�r Tabelle `tbl_group_management`
--

INSERT INTO `tbl_group_management` (`ID`, `groupname`, `groupdescription`, `sector`) VALUES
(1, 'stdUser', 'Standard', 'standard'),
(2, 'admin', 'rules over the world', 'technical_support');

--
-- Tabellenstruktur f�r Tabelle `tbl_Item`
--

CREATE TABLE IF NOT EXISTS `tbl_Item` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `typeID` int(10) unsigned NOT NULL,
  `refname` char(50) CHARACTER SET latin1 COLLATE latin1_german1_ci NOT NULL,
  `data` text CHARACTER SET latin1 COLLATE latin1_german1_ci NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

--
-- Tabellenstruktur f�r Tabelle `tbl_qportal_doc_overview`
--

CREATE TABLE IF NOT EXISTS `tbl_qportal_doc_overview` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `txt_doc_name` char(255) CHARACTER SET latin1 COLLATE latin1_german1_ci NOT NULL,
  `txt_doc_URL` char(255) CHARACTER SET latin1 COLLATE latin1_german1_ci NOT NULL,
  `txt_doc_URI` char(255) NOT NULL,
  `txt_doc_label` char(255) NOT NULL,
  `txt_doc_comment` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `txt_doc_name` (`txt_doc_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;

--
-- Tabellenstruktur f�r Tabelle `tbl_qportal_doc_ref`
--

CREATE TABLE IF NOT EXISTS `tbl_qportal_doc_ref` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `int_doc_id` int(11) NOT NULL,
  `int_import_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;

--
-- Tabellenstruktur f�r Tabelle `tbl_user_management`
--

CREATE TABLE IF NOT EXISTS `tbl_user_management` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `User` char(120) NOT NULL,
  `Key` char(120) NOT NULL,
  `forename` char(60) NOT NULL DEFAULT 'Mr.',
  `surname` char(60) NOT NULL DEFAULT 'Anderson',
  `securityclass` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Daten f�r Tabelle `tbl_user_management`
--

INSERT INTO `tbl_user_management` (`ID`, `User`, `Key`, `forename`, `surname`, `securityclass`) VALUES
(1, 'Admin', 'fcdd354fa9f1afca57ad7a956a05922a', 'Super', 'Grossmeister', 10);

--
-- Tabellenstruktur f�r Tabelle `tbl_user_to_group`
--

CREATE TABLE IF NOT EXISTS `tbl_user_to_group` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

--
-- Daten f�r Tabelle `tbl_user_to_group`
--

INSERT INTO `tbl_user_to_group` (`ID`, `user_id`, `group_id`) VALUES
(1, 1, 2);


