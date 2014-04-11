<?php

/**
 * @Project NUKEVIET 4.x
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @Copyright (C) 2014 VINADES.,JSC. All rights reserved
 * @License GNU/GPL version 2 or any later version
 * @Createdate 2-9-2010 14:43
 * @Development version theme control
 */

if( ! defined( 'NV_IS_FILE_THEMES' ) ) die( 'Stop!!!' );

$array_bid = $nv_Request->get_array( 'bl', 'post' );
$func_id = $nv_Request->get_int( 'func_id', 'post' );

$position = $nv_Request->get_string( 'position', 'post' );

if( ! empty( $array_bid ) && ! empty( $position ) )
{
	$pos_new = '[' . $position . ']';

	$sth = $db->prepare( 'SELECT bid, theme, position FROM ' . NV_BLOCKS_TABLE . '_groups WHERE position != :position AND bid IN (' . implode( ',', $array_bid ) . ')' );
	$sth->bindParam( ':position', $pos_new, PDO::PARAM_STR );
	$sth->execute();
	$row = $sth->fetch( 3 );
	if( !empty( $row ) )
	{
		list( $bid, $theme, $pos_old ) = $row;

		$sth = $db->prepare( 'UPDATE ' . NV_BLOCKS_TABLE . '_groups SET position= :position, weight=2147483647 WHERE bid=' . $bid );
		$sth->bindParam( ':position', $pos_new, PDO::PARAM_STR );
		$sth->execute();

		$db->query( 'UPDATE ' . NV_BLOCKS_TABLE . '_weight SET weight=2147483647 WHERE bid=' . $bid );

		//Update weight for old position
		$sth = $db->prepare( 'SELECT bid FROM ' . NV_BLOCKS_TABLE . '_groups WHERE theme= :theme AND position=:position ORDER BY weight ASC' );
		$sth->bindParam( ':theme', $theme, PDO::PARAM_STR );
		$sth->bindParam( ':position', $pos_old, PDO::PARAM_STR );
		$sth->execute();

		$weight = 0;
		while( list( $bid_i ) = $sth->fetch( 3 ) )
		{
			++$weight;
			$db->query( 'UPDATE ' . NV_BLOCKS_TABLE . '_groups SET weight=' . $weight . ' WHERE bid=' . $bid_i );
		}

		if( $weight )
		{
			$func_id_old = $weight = 0;

			$sth = $db->prepare( 'SELECT t1.bid, t1.func_id FROM ' . NV_BLOCKS_TABLE . '_weight t1
				INNER JOIN ' . NV_BLOCKS_TABLE . '_groups t2 ON t1.bid = t2.bid
				WHERE t2.theme= :theme AND t2.position= :position ORDER BY t1.func_id ASC, t1.weight ASC' );
			$sth->bindParam( ':theme', $theme, PDO::PARAM_STR );
			$sth->bindParam( ':position', $pos_old, PDO::PARAM_STR );
			$sth->execute();
			while( list( $bid_i, $func_id_i ) = $sth->fetch( 3 ) )
			{
				if( $func_id_i == $func_id_old )
				{
					++$weight;
				}
				else
				{
					$weight = 1;
					$func_id_old = $func_id_i;
				}
				$db->query( 'UPDATE ' . NV_BLOCKS_TABLE . '_weight SET weight=' . $weight . ' WHERE bid=' . $bid_i . ' AND func_id=' . $func_id_i );
			}
		}

		//Update weight for news position
		$sth = $db->prepare( 'SELECT bid FROM ' . NV_BLOCKS_TABLE . '_groups
			WHERE theme= :theme AND position= :position
			ORDER BY weight ASC' );
		$sth->bindParam( ':theme', $theme, PDO::PARAM_STR );
		$sth->bindParam( ':position', $pos_new, PDO::PARAM_STR );
		$sth->execute();

		$weight = 0;
		while( list( $bid_i ) = $sth->fetch( 3 ) )
		{
			++$weight;
			$db->query( 'UPDATE ' . NV_BLOCKS_TABLE . '_groups SET weight=' . $weight . ' WHERE bid=' . $bid_i );
		}

		$func_id_old = $weight = 0;
		$sth = $db->prepare( 'SELECT t1.bid, t1.func_id FROM ' . NV_BLOCKS_TABLE . '_weight t1
			INNER JOIN ' . NV_BLOCKS_TABLE . '_groups t2 ON t1.bid = t2.bid
			WHERE t2.theme= :theme AND t2.position= :position
			ORDER BY t1.func_id ASC, t1.weight ASC' );
		$sth->bindParam( ':theme', $theme, PDO::PARAM_STR );
		$sth->bindParam( ':position', $pos_new, PDO::PARAM_STR );
		$sth->execute();
		while( list( $bid_i, $func_id_i ) = $sth->fetch( 3 ) )
		{
			if( $func_id_i == $func_id_old )
			{
				++$weight;
			}
			else
			{
				$weight = 1;
				$func_id_old = $func_id_i;
			}
			$db->query( 'UPDATE ' . NV_BLOCKS_TABLE . '_weight SET weight=' . $weight . ' WHERE bid=' . $bid_i . ' AND func_id=' . $func_id_i );
		}

	}
}

$weight = 1;

if( ! empty( $array_bid ) and $func_id > 0 )
{
	foreach( $array_bid as $bid )
	{
		$db->query( 'UPDATE ' . NV_BLOCKS_TABLE . '_weight SET weight = ' . $weight . ' WHERE bid = ' . $bid . ' AND func_id=' . $func_id );
		++$weight;
	}
}

nv_del_moduleCache( 'themes' );

$db->query( 'OPTIMIZE TABLE ' . NV_BLOCKS_TABLE . '_groups' );
$db->query( 'OPTIMIZE TABLE ' . NV_BLOCKS_TABLE . '_weight' );

die( 'OK_' . $func_id );

?>