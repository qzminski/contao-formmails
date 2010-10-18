<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Andreas Schempp 2010
 * @author     Andreas Schempp <andreas@schempp.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 * @version    $Id$
 */


class FormMails extends Frontend
{
	
	public function processFormData($arrPost, $arrForm, $arrFiles)
	{
		if ($arrForm['cmail'])
		{
			$blnSent = false;

			$objEmail = new Email();
			$objEmail->subject = $arrForm['cmailSubject'] = $this->parseSimpleTokens($this->replaceInsertTags($arrForm['cmailSubject']), $arrPost);
			$objEmail->text = $arrForm['cmailMessage'] = $this->parseSimpleTokens($this->replaceInsertTags($arrForm['cmailMessage']), $arrPost);
			
			if ($arrForm['cmailSender'] == '')
			{
				$objEmail->from = $arrForm['cmailSender'] = $GLOBALS['TL_ADMIN_EMAIL'];
				
				if ($GLOBALS['TL_ADMIN_NAME'] != '')
				{
					$objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'];
					$arrForm['cmailSender'] = $GLOBALS['TL_ADMIN_NAME'] . ' <' . $arrForm['cmailSender'] . '>';
				}
			}
			else
			{
				list($strName, $strAddress) = $this->splitFriendlyName($arrForm['cmailSender']);
				
				$objEmail->from = $strAddress;
				$objEmail->fromName = $strName;
			}
			
			if ($arrForm['cmailRecipient'] && (!isset($arrForm['cc']) || $arrForm['cc'] == '1'))
			{
				$objField = $this->Database->execute("SELECT * FROM tl_form_field WHERE id={$arrForm['cmailRecipient']}");
				
				if ($objField->numRows && $this->isValidEmailAddress($arrPost[$objField->name]))
				{
					$arrForm['cmailRecipient'] = $arrPost[$objField->name];
					$objEmail->sendTo($arrForm['cmailRecipient']);
					$blnSent = true;
				}
			}
			
			if ($arrForm['cmailBCC'] != '')
			{
				$arrBCC = trimsplit(',', $arrForm['cmailBCC']);
				
				foreach( $arrBCC as $strRecipient )
				{
					$objEmail->sendBcc($strRecipient);
				}
				
				$blnSent = true;
			}
			
			// Only add record if an email was sent
			if ($blnSent)
			{
				$this->Database->prepare("INSERT INTO tl_form_mails (pid,tstamp,cmailSender,cmailSubject,cmailRecipient,cmailBcc,cmailMessage,form_post,form_files) VALUES (?,?,?,?,?,?,?,?,?)")
							   ->execute($arrForm['id'], time(), $arrForm['cmailSender'], $arrForm['cmailSubject'], $arrForm['cmailRecipient'], $arrForm['cmailBcc'], nl2br($arrForm['cmailMessage']), serialize($arrPost), serialize($arrFiles));
			}
		}
	}
}
