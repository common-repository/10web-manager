<?php
$configs = \Tenweb_Manager\Helper::get_configs();
$def_configs = \Tenweb_Manager\Helper::get_default_configs();
?>

<style>
    #tenweb_config_table {
        width: 95%;
    }

    #tenweb_config_table td {
        border: 1px solid #c4c4c4;
        padding: 12px;
    }

    #tenweb_config_table td:nth-child(1) {
        width: 25%;
    }

    #tenweb_config_table td:nth-child(2) {
        width: 40%;
    }

    #tenweb_save_config {
        padding: 6px;
        margin-bottom: 6px;
    }
</style>

<h2><?php echo "Configs: " ?></h2>
<button id="tenweb_save_config">Save</button>
<table id="tenweb_config_table">
    <tbody>
    <thead>
    <tr>
        <th>Label</th>
        <th>Value</th>
        <th>Default Value</th>
    </tr>
    </thead>
    <tr>
        <td><label for="tenweb_migration_debug">TENWEB_MIGRATION_DEBUG:</label></td>
        <td><input type="text" id="tenweb_migration_debug"
                   value="<?php echo $configs['TENWEB_MIGRATION_DEBUG']; ?>"></td>
        <td>0</td>
    </tr>
    <tr>
        <td><label for="tenweb_migration_encrypt_db">TENWEB_MIGRATION_ENCRYPT_DB:</label></td>
        <td><input type="text" id="tenweb_migration_encrypt_db"
                   value="<?php echo $configs['TENWEB_MIGRATION_ENCRYPT_DB']; ?>"></td>
        <td>0</td>
    </tr>
    <tr>
        <td><label for="tenweb_migration_archive_type">TENWEB_MIGRATION_ARCHIVE_TYPE: </label></td>
        <td>
            <select id="tenweb_migration_archive_type">
                <option value="gzip" <?php echo $configs['TENWEB_MIGRATION_ARCHIVE_TYPE'] == "gzip" ? "selected" : ""; ?> >
                    gzip
                </option>
                <option value="zip" <?php echo $configs['TENWEB_MIGRATION_ARCHIVE_TYPE'] == "zip" ? "selected" : ""; ?>>
                    zip
                <option value="nelexa_zip" <?php echo $configs['TENWEB_MIGRATION_ARCHIVE_TYPE'] == "nelexa_zip" ? "selected" : ""; ?>>
                    nelexa_zip
                <option value="nelexa_zip_stream" <?php echo $configs['TENWEB_MIGRATION_ARCHIVE_TYPE'] == "nelexa_zip_stream" ? "selected" : ""; ?>>
                    nelexa_zip_stream
                <option value="alchemy_zip" <?php echo $configs['TENWEB_MIGRATION_ARCHIVE_TYPE'] == "alchemy_zip" ? "selected" : ""; ?>>
                    alchemy_zip
                </option>
            </select>
        </td>
        <td><?php echo $def_configs['TENWEB_MIGRATION_ARCHIVE_TYPE_DEFAULT']; ?></td>
    </tr>
    <tr>
        <td><label for="tenweb_migration_max_files_restart">TENWEB_MIGRATION_MAX_FILES_RESTART:</label></td>
        <td><input type="text" id="tenweb_migration_max_files_restart"
                   value=<?php echo $configs['TENWEB_MIGRATION_MAX_FILES_RESTART']; ?>></td>
        <td><?php echo $def_configs['TENWEB_MIGRATION_MAX_FILES_RESTART_DEFAULT']; ?></td>
    </tr>
    <tr>
        <td><label for="tenweb_migration_bulk_files_count">TENWEB_MIGRATION_BULK_FILES_COUNT:</label></td>
        <td><input type="text" id="tenweb_migration_bulk_files_count"
                   value=<?php echo $configs['TENWEB_MIGRATION_BULK_FILES_COUNT']; ?>></td>
        <td><?php echo $def_configs['TENWEB_MIGRATION_BULK_FILES_COUNT_DEFAULT']; ?></td>
    </tr>
    <tr>
        <td><label for="tenweb_migration_max_db_rows_restart">TENWEB_MIGRATION_MAX_DB_ROWS_RESTART:</label></td>
        <td><input type="text" id="tenweb_migration_max_db_rows_restart"
                   value=<?php echo $configs['TENWEB_MIGRATION_MAX_DB_ROWS_RESTART']; ?>></td>
        <td><?php echo $def_configs['TENWEB_MIGRATION_MAX_DB_ROWS_RESTART_DEFAULT']; ?></td>
    </tr>
    <tr>
        <td><label for="tenweb_migration_bulk_db_rows_count">TENWEB_MIGRATION_BULK_DB_ROWS_COUNT:</label></td>
        <td><input type="text" id="tenweb_migration_bulk_db_rows_count"
                   value=<?php echo $configs['TENWEB_MIGRATION_BULK_DB_ROWS_COUNT']; ?>></td>
        <td><?php echo $def_configs['TENWEB_MIGRATION_BULK_DB_ROWS_COUNT_DEFAULT']; ?></td>
    </tr>
    <tr>
        <td><label for="tenweb_migration_file_size_limit">TENWEB_MIGRATION_FILE_SIZE_LIMIT:</label></td>
        <td><input type="text" id="tenweb_migration_file_size_limit"
                   value=<?php echo $configs['TENWEB_MIGRATION_FILE_SIZE_LIMIT']; ?>></td>
        <td><?php echo $def_configs['TENWEB_MIGRATION_FILE_SIZE_LIMIT_DEFAULT']; ?></td>
    </tr>
    <tr>
        <td><label for="tenweb_migration_multiple_archives">TENWEB_MIGRATION_MULTIPLE_ARCHIVES:</label></td>
        <td><input type="text" id="tenweb_migration_multiple_archives"
                   value=<?php echo $configs['TENWEB_MIGRATION_MULTIPLE_ARCHIVES']; ?>></td>
        <td><?php echo $def_configs['TENWEB_MIGRATION_MULTIPLE_ARCHIVES_DEFAULT']; ?></td>
    </tr>
    <tr>
        <td><label for="tenweb_migration_exec_time_offset">TENWEB_MIGRATION_EXEC_TIME_OFFSET:</label></td>
        <td><input type="text" id="tenweb_migration_exec_time_offset"
                   value=<?php echo $configs['TENWEB_MIGRATION_EXEC_TIME_OFFSET']; ?>></td>
        <td><?php echo $def_configs['TENWEB_MIGRATION_EXEC_TIME_OFFSET_DEFAULT']; ?></td>
    </tr>
    <tr>
        <td><label for="migration_upload_archive_s3">TENWEB_MIGRATION_UPLOAD_ARCHIVE_S3:</label></td>
        <td><input type="text" id="tenweb_migration_upload_archive_s3"
                   value=<?php echo $configs['TENWEB_MIGRATION_UPLOAD_ARCHIVE_S3']; ?>></td>
        <td><?php echo $def_configs['TENWEB_MIGRATION_UPLOAD_ARCHIVE_S3']; ?></td>
    </tr>
    <tr>
        <td><label for="migration_multipart_upload_chunk_size">TENWEB_MIGRATION_MULTIPART_UPLOAD_CHUNK_SIZE:</label></td>
        <td><input type="text" id="tenweb_migration_multipart_upload_chunk_size"
                   value=<?php echo $configs['TENWEB_MIGRATION_MULTIPART_UPLOAD_CHUNK_SIZE']; ?>></td>
        <td><?php echo $def_configs['TENWEB_MIGRATION_MULTIPART_UPLOAD_CHUNK_SIZE']; ?></td>
    </tr>
    </tbody>
</table>

<h2><?php echo "Actions: " ?></h2>
<table>
    <tr>
        <td><label for="tenweb_delete_ip_from_banned_list">Delete ip from banned list:</label></td>
        <td>
            <input type="text" placeholder="Enter IP address" id="tenweb_banned_ips" style="width:500px"/>
            <div><small>For multiple ips, enter comma separated ips (e.g. 127.0.0.1,127.0.0.2)</small></div>
        </td>
        <td>
            <button id="tenweb_delete_banned_ips_options">Delete</button>
        </td>
    </tr>
</table>
