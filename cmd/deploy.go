package cmd

import "github.com/urfave/cli/v2"

func Deploy(c *cli.Context) error {
	return nil
}

func GetDeployCommand() *cli.Command {
	return &cli.Command{
		Name:    "deploy",
		Aliases: []string{"d"},
		Usage:   "Deploy your pull request to Laravel Forge.",
		Action:  Deploy,
	}
}
